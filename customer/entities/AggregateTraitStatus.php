<?php

declare(strict_types=1);

namespace customer\entities;

use BadMethodCallException;

trait AggregateTraitStatus
{
    /**
     * Indicates entity is active (not blocked).
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        $this->assertStatus();
        return $this->attributes['status'] === Status::STATUS_ACTIVE;
    }

    /**
     * Status get mutator
     * 
     * @return Status
     */
    public function getStatusAttribute($value): Status
    {
        $this->assertStatus();
        return new Status($value);
    }

    /**
     * Block entity
     * 
     * @return void
     */
    public function block(): void
    {
        $this->assertStatus();
        $this->status = Status::STATUS_INACTIVE;
    }

    /**
     * Unblock entity
     * 
     * @return void
     */
    public function unBlock(): void
    {
        $this->assertStatus();
        $this->status = Status::STATUS_ACTIVE;
    }

    /**
     * Check model proper confifuration to work with statuses.
     * 
     * @return void
     * @throws BadMethodCallException If attribute "status" does not exists in aggregate.
     */
    private function assertStatus()
    {
        if (!isset($this->attributes['status'])) {
            throw new BadMethodCallException('Attribute "status" undefined in aggregate: ' . get_class($this));
        }
    }
}
