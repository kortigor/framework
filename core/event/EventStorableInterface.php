<?php

declare(strict_types=1);

namespace core\event;

/**
 * EventStorableInterface describes entity with storable events.
 */
interface EventStorableInterface
{
    /**
     * Retrieve and clear stored events.
     * 
     * @return object[]
     */
    public function releaseEvents(): array;
}