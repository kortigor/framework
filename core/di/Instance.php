<?php

declare(strict_types=1);

namespace core\di;

use core\exception\ContainerException;
use core\exception\InvalidConfigException;
use core\interfaces\ContainerInterface;

/**
 * Instance represents a reference to a named object in a dependency injection (DI) container.
 *
 * Possible to use `get()` to obtain the actual object referenced by `id`.
 */
class Instance
{
    /**
     * Constructor.
     * 
     * @param string $id Component ID, class name, interface name or alias name
     */
    protected function __construct(private string $id)
    {
        if (empty($id)) {
            throw new ContainerException('Empty instance id.');
        }
    }

    /**
     * Creates a new Instance object.
     * 
     * @param string $id Component ID, class name, interface name or alias name
     * @return static New Instance object.
     */
    public static function of(string $id): static
    {
        return new static($id);
    }

    /**
     * Returns the actual object referenced by this Instance object.
     * 
     * @param ContainerInterface $container The container used to locate the referenced object.
     * @return object the actual object referenced by this Instance object.
     * @throws ContainerException If unable to get actual object.
     */
    public function get(ContainerInterface $container = null): object
    {
        return $container->get($this->id);
    }

    /**
     * Get component ID, class name, interface name or alias name
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array $state
     * @return self
     * @throws InvalidConfigException when $state property does not contain `id` parameter
     * @see var_export()
     */
    public static function __set_state($state)
    {
        if (!isset($state['id'])) {
            throw new InvalidConfigException('Failed to instantiate class "Instance". Required parameter "id" is missing');
        }

        return new self($state['id']);
    }
}