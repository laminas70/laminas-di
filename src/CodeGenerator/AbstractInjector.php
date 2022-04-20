<?php

declare(strict_types=1);

namespace Laminas\Di\CodeGenerator;

use Laminas\Di\DefaultContainer;
use Laminas\Di\InjectorInterface;
use Psr\Container\ContainerInterface;

/**
 * Abstract class for code generated dependency injectors
 */
abstract class AbstractInjector implements InjectorInterface
{
    /** @var string[]|FactoryInterface[] */
    protected $factories = [];

    /** @var FactoryInterface[] */
    private $factoryInstances = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var InjectorInterface
     */
    private $injector;

    /**
     * {@inheritDoc}
     *
     * @param InjectorInterface       $injector
     * @param ContainerInterface|null $container
     */
    public function __construct(InjectorInterface $injector, ContainerInterface $container = null)
    {
        $this->injector  = $injector;
        $this->container = $container ?: new DefaultContainer($this);

        $this->loadFactoryList();
    }

    /**
     * Init factory list
     *
     * @return void
     */
    abstract protected function loadFactoryList();

    /**
     * @param string           $type
     * @param FactoryInterface $factory
     *
     * @return void
     */
    private function setFactory(string $type, FactoryInterface $factory)
    {
        $this->factoryInstances[$type] = $factory;
    }

    private function getFactory(string $type): FactoryInterface
    {
        if (isset($this->factoryInstances[$type])) {
            return $this->factoryInstances[$type];
        }

        $factoryClass = $this->factories[$type];
        $factory      = $factoryClass instanceof FactoryInterface ? $factoryClass : new $factoryClass();

        $this->setFactory($type, $factory);

        return $factory;
    }

    /**
     * @param string $name
     */
    public function canCreate($name): bool
    {
        return $this->hasFactory($name) || $this->injector->canCreate($name);
    }

    private function hasFactory(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    /** @return mixed
     * @param string $name
     * @param mixed[] $options */
    public function create($name, $options = [])
    {
        if ($this->hasFactory($name)) {
            return $this->getFactory($name)->create($this->container, $options);
        }

        return $this->injector->create($name, $options);
    }
}
