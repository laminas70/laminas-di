<?php

declare(strict_types=1);

namespace Laminas\Di\CodeGenerator;

use Psr\Container\ContainerInterface;

interface FactoryInterface
{
    /**
     * Create an instance
     *
     * @return object
     * @param \Psr\Container\ContainerInterface $container
     * @param mixed[] $options
     */
    public function create($container, $options);
}
