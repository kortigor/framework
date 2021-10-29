<?php

declare(strict_types=1);

namespace customer\entities;

trait AggregateTraitStatusEmployee
{
    use AggregateTraitStatus;

    /**
     * Indicates employee is active (not blocked).
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        $this->assertStatus();
        return $this->attributes['status'] === StatusEmployee::STATUS_ACTIVE;
    }

    /**
     * Status get mutator
     * 
     * @return StatusEmployee
     */
    public function getStatusAttribute($value): StatusEmployee
    {
        $this->assertStatus();
        return new StatusEmployee($value);
    }

    /**
     * Block entity
     * 
     * @return void
     */
    public function block(): void
    {
        $this->assertStatus();
        $this->status = StatusEmployee::STATUS_INACTIVE;
    }

    /**
     * Unblock entity
     * 
     * @return void
     */
    public function unBlock(): void
    {
        $this->assertStatus();
        $this->status = StatusEmployee::STATUS_ACTIVE;
    }
}
