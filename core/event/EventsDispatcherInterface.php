<?php

declare(strict_types=1);

namespace core\event;

/**
 * Defines a dispatcher for collection of events.
 */
interface EventsDispatcherInterface extends EventDispatcherInterface
{
    /**
     * Dispatch collection of events
     * 
     * @param iterable $events Events collection to dispatch
     * 
     * @return iterable The Events that was passed, now modified by listeners.
     */
    public function dispatchAll(iterable $events): iterable;
}
