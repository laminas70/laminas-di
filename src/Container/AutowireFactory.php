<?php

declare(strict_types=1);

namespace Laminas\Di\Container;

use Laminas\Di\Exception;
use Laminas\Di\InjectorInterface;
use Psr\Container\ContainerInterface;

/**
 * Create instances with autowiring
 */
class AutowireFactory
{
    /**
     * Retrieves the injector from a container
     *
     * @param ContainerInterface $container The container context for this factory
     * @return InjectorInterface The dependency injector
     * @throws Exception\RuntimeException When no dependency injector is available.
     */
    private function getInjector(ContainerInterface $container)
    {
        $injector = $container->get(InjectorInterface::class);

        if (! $injector instanceof InjectorInterface) {
            throw new Exception\RuntimeException(
                'Could not get a dependency injector form the container implementation'
            );
        }

        return $injector;
    }

    /**
     * Check creatability of the requested name
     *
     * @param string $requestedName
     * @return bool
     * @param \Psr\Container\ContainerInterface $container
     */
    public function canCreate($container, $requestedName)
    {
        if (! $container->has(InjectorInterface::class)) {
            return false;
        }

        return $this->getInjector($container)->canCreate((string) $requestedName);
    }

    /**
     * Create an instance
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return object
     * @throws Exception\ExceptionInterface
     */
    public function create($container, $requestedName, $options = null)
    {
        return $this->getInjector($container)->create($requestedName, $options ?: []);
    }

    /**
     * Make invokable and implement the laminas-service factory pattern
     *
     * @param ContainerInterface $container
     * @param                    $requestedName
     * @param array|null         $options
     *
     * @return object
     * @throws Exception\ExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return $this->create($container, (string) $requestedName, $options);
    }
}
