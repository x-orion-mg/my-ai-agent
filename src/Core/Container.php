<?php

declare(strict_types=1);

namespace MyAIAgent\Core;

use RuntimeException;

final class Container
{
    /**
     * @var array<string, callable(self): mixed>
     */
    private array $factories = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a singleton factory.
     */
    public function singleton(
        string $id,
        callable $factory
    ): void {
        $this->factories[$id] = $factory;
    }

    /**
     * Register an already-created instance.
     */
    public function set(
        string $id,
        mixed $instance
    ): void {
        $this->instances[$id] = $instance;
    }

    /**
     * Check whether a service exists.
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->factories[$id]);
    }

    /**
     * Resolve a service.
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new RuntimeException(
                sprintf(
                    'Service "%s" not found in container.',
                    $id
                )
            );
        }

        $this->instances[$id] = ($this->factories[$id])($this);

        return $this->instances[$id];
    }
}
