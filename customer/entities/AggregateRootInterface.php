<?php

declare(strict_types=1);

namespace customer\entities;

interface AggregateRootInterface
{
    /**
     * Save aggregate via transaction.
     * 
     * @return void
     * @throws Throwable if transaction fails.
     */
    public function saveAggregate(): void;

    /**
     * Delete aggregate via transaction.
     * 
     * @return void
     * @throws Throwable if transaction fails.
     */
    public function deleteAggregate(): void;

    /**
     * Indicates aggregate is active (not blocked).
     * 
     * @return bool
     */
    public function isActive(): bool;
}
