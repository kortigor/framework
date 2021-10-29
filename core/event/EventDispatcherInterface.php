<?php

declare(strict_types=1);

namespace core\event;

/**
 * Defines a dispatcher for events.
 * 
 * A Dispatcher SHOULD compose a Listener Provider to determine relevant listeners.
 * It is RECOMMENDED that a Listener Provider be implemented as a distinct object
 * from the Dispatcher but that is NOT REQUIRED.
 */
interface EventDispatcherInterface
{
    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event The object to process.
     *
     * @return object The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event): object;
}
