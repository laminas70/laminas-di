<?php

declare(strict_types=1);

namespace Laminas\Di\Container\ServiceManager;

use Interop\Container\ContainerInterface;
use Laminas\Di\Container\AutowireFactory as GenericAutowireFactory;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create instances with autowiring
 *
 * This class is purely for compatibility with Laminas\ServiceManager interface which requires container-interop
 */
class AutowireFactory implements AbstractFactoryInterface
{
    /**
     * @var GenericAutowireFactory
     */
    private $factory;

    /**
     * @param GenericAutowireFactory|null $factory
     */
    public function __construct(GenericAutowireFactory $factory = null)
    {
        $this->factory = $factory ? : new GenericAutowireFactory();
    }

    /**
     * Check creatability of the requested name
     *
     * @param string $requestedName
     * @return bool
     * @param \Interop\Container\ContainerInterface $container
     */
    public function canCreate($container, $requestedName)
    {
        return $this->factory->canCreate($container, $requestedName);
    }

    /**
     * Make invokable and implement the laminas-service factory pattern
     *
     * @param ContainerInterface $container
     * @param                    $requestedName
     * @param array|null         $options
     *
     * @return object
     * @throws \Laminas\Di\Exception\ExceptionInterface
     */
    public function __invoke($container, $requestedName, array $options = null)
    {
        return $this->factory->create($container, (string) $requestedName, $options);
    }
}
