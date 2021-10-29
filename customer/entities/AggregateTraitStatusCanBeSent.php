<?php

declare(strict_types=1);

namespace customer\entities;

trait AggregateTraitStatusCanBeSent
{
    use AggregateTraitStatus;

    /**
     * Indicates entity is active (not blocked).
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        $this->assertStatus();
        return $this->attributes['status'] === StatusCanBeSent::STATUS_ACTIVE;
    }

    /**
     * Status get mutator
     * 
     * @return StatusCanBeSent
     */
    public function getStatusAttribute($value): StatusCanBeSent
    {
        $this->assertStatus();
        return new StatusCanBeSent($value);
    }

    /**
     * Block entity
     * 
     * @return void
     */
    public function block(): void
    {
        $this->assertStatus();
        $this->status = StatusCanBeSent::STATUS_INACTIVE;
    }

    /**
     * Unblock entity
     * 
     * @return void
     */
    public function unBlock(): void
    {
        $this->assertStatus();
        $this->status = StatusCanBeSent::STATUS_ACTIVE;
    }
}
