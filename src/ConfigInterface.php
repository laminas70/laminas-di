<?php

declare(strict_types=1);

namespace Laminas\Di;

/**
 * Provides the instance and resolver configuration
 */
interface ConfigInterface
{
    /**
     * Check if the provided type name is aliased
     * @param string $name
     */
    public function isAlias($name): bool;

    /**
     * @return string[]
     */
    public function getConfiguredTypeNames(): array;

    /**
     * Returns the actual class name for an alias
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getClassForAlias($name);

    /**
     * Returns the instanciation parameters for the given type
     *
     * @param  string $type The alias or class name
     * @return array The configured parameter hash
     */
    public function getParameters($type): array;

    /**
     * Set the instanciation parameters for the given type
     * @param string $type
     * @param mixed[] $params
     */
    public function setParameters($type, $params);

    /**
     * Configured type preference
     *
     * @param string      $type
     * @param string|null $contextClass
     *
     * @return string|null
     */
    public function getTypePreference($type, $contextClass = null);
}
