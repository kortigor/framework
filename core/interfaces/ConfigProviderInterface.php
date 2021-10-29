<?php

declare(strict_types=1);

namespace core\interfaces;

/**
 * ConfigProviderInterface is the interface that should be implemented by any class providing config data
 */
interface ConfigProviderInterface
{
    /**
     * Get config data.
     * 
     * @return array
     * @see \core\data\Setings::addCofig()
     */
    public function toArray(): array;
}
