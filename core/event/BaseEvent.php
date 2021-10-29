<?php

declare(strict_types=1);

namespace core\event;

/**
 * Event is the base class for classes containing event data.
 *
 * This class contains no event data. It is used by events that do not pass
 * state information to an event handler when an event is raised.
 *
 * You can call the method stopPropagation() to abort the execution of
 * further listeners in your event listener.
 */
abstract class BaseEvent implements StoppableEventInterface
{
    const EVENT_NAME = 'BasicEvent';

    protected bool $propagationStopped = false;

    /**
     * {@inheritdoc}
     */
    final public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    final public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}