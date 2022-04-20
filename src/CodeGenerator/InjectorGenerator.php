<?php

declare(strict_types=1);

namespace Laminas\Di\CodeGenerator;

use Laminas\Di\CodeGenerator\AutoloadGenerator;
use Laminas\Di\CodeGenerator\FactoryGenerator;
use Laminas\Di\ConfigInterface;
use Laminas\Di\Definition\DefinitionInterface;
use Laminas\Di\Resolver\DependencyResolverInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplFileObject;
use Throwable;

use function array_keys;
use function array_map;
use function assert;
use function file_get_contents;
use function implode;
use function is_string;
use function sprintf;
use function str_repeat;
use function strtr;
use function var_export;

/**
 * Generator for the dependency injector
 *
 * Generates a Injector class that will use a generated factory for a requested
 * type, if available. This factory will contained pre-resolved dependencies
 * from the provided configuration, definition and resolver instances.
 */
class InjectorGenerator
{
    use GeneratorTrait;

    const FACTORY_LIST_TEMPLATE = __DIR__ . '/../../templates/factory-list.template';
    const INJECTOR_TEMPLATE     = __DIR__ . '/../../templates/injector.template';
    const INDENTATION_SPACES    = 4;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @deprecated
     *
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var FactoryGenerator
     */
    private $factoryGenerator;

    /**
     * @var AutoloadGenerator
     */
    private $autoloadGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructs the compiler instance
     *
     * @param ConfigInterface             $config The configuration to compile from
     * @param DependencyResolverInterface $resolver The resolver to utilize
     * @param string|null                 $namespace Namespace to use for generated class; defaults
     *                     to Laminas\Di\Generated.
     * @param LoggerInterface|null        $logger An optional logger instance to log failures
     *            and processed classes.
     */
    public function __construct(
        ConfigInterface $config,
        DependencyResolverInterface $resolver,
        string $namespace = null,
        LoggerInterface $logger = null
    ) {
        $this->config            = $config;
        $this->namespace         = $namespace ? : 'Laminas\Di\Generated';
        $this->factoryGenerator  = new FactoryGenerator($config, $resolver, $this->namespace . '\Factory');
        $this->autoloadGenerator = new AutoloadGenerator($this->namespace);
        $this->logger            = $logger ?? new NullLogger();
    }

    /**
     * @param string $templateFile
     * @param string $outputFile
     * @param array  $replacements
     *
     * @return void
     */
    private function buildFromTemplate(string $templateFile, string $outputFile, array $replacements)
    {
        $template = file_get_contents($templateFile);

        assert(is_string($template));

        $code = strtr($template, $replacements);
        $file = new SplFileObject($outputFile, 'w');

        $file->fwrite($code);
        $file->fflush();
    }

    /**
     * @return void
     */
    private function generateInjector()
    {
        $this->buildFromTemplate(
            self::INJECTOR_TEMPLATE,
            sprintf('%s/GeneratedInjector.php', $this->outputDirectory),
            [
                '%namespace%' => $this->namespace ? "namespace {$this->namespace};\n" : '',
            ]
        );
    }

    /**
     * @param array $factories
     *
     * @return void
     */
    private function generateFactoryList(array $factories)
    {
        $indentation = sprintf("\n%s", str_repeat(' ', self::INDENTATION_SPACES));
        $codeLines   = array_map(
            function (string $key, string $value): string {
                return sprintf('%s => %s,', var_export($key, true), var_export($value, true));
            },
            array_keys($factories),
            $factories
        );

        $this->buildFromTemplate(self::FACTORY_LIST_TEMPLATE, sprintf('%s/factories.php', $this->outputDirectory), [
            '%factories%' => implode($indentation, $codeLines),
        ]);
    }

    /**
     * @param string $class
     * @param array  $factories
     *
     * @return void
     */
    private function generateTypeFactory(string $class, array &$factories)
    {
        if (isset($factories[$class])) {
            return;
        }

        $this->logger->debug(sprintf('Generating factory for class "%s"', $class));

        try {
            $factory = $this->factoryGenerator->generate($class);

            if ($factory) {
                $factories[$class] = $factory;
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Could not create factory for "%s": %s',
                $class,
                $e->getMessage()
            ));
        }
    }

    /**
     * @return void
     */
    private function generateAutoload()
    {
        $addFactoryPrefix = function($value) {
            return 'Factory/' . $value;
        };

        $classmap = array_map($addFactoryPrefix, $this->factoryGenerator->getClassmap());

        $classmap[$this->namespace . '\\GeneratedInjector'] = 'GeneratedInjector.php';

        $this->autoloadGenerator->generate($classmap);
    }

    /**
     * Returns the namespace this generator uses
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Generate the injector
     *
     * This will generate the injector and its factories into the output directory
     *
     * @param string[] $classes
     * @return void
     */
    public function generate($classes = [])
    {
        $this->ensureOutputDirectory();
        $this->factoryGenerator->setOutputDirectory($this->outputDirectory . '/Factory');
        $this->autoloadGenerator->setOutputDirectory($this->outputDirectory);
        $factories = [];

        foreach ($classes as $class) {
            $this->generateTypeFactory($class, $factories);
        }

        foreach ($this->config->getConfiguredTypeNames() as $type) {
            $this->generateTypeFactory($type, $factories);
        }

        $this->generateAutoload();
        $this->generateInjector();
        $this->generateFactoryList($factories);
    }
}
