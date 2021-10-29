<?php

declare(strict_types=1);

namespace customer\entities;

/**
 * Trait with implementation of \core\event\EventStorableInterface
 */
trait EventTrait
{
    /**
     * @var array Events storage.
     */
    private array $events = [];

    /**
     * Store event inside entity.
     * 
     * @param object $event Event to record.
     * 
     * @return void
     */
    protected function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Remove event from entity.
     * 
     * @param object $event
     * 
     * @return int Number of removed events
     */
    protected function removeEvent(object $event): int
    {
        $nameOrObject = get_class($event);
        $i = 0;

        foreach ($this->events as $key => $ev) {
            if ($ev instanceof $nameOrObject) {
                unset($this->events[$key]);
                $i++;
            }
        }

        return $i;
    }

    /**
     * Retrieve and clear stored events.
     * 
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    /**
     * Whether entity has an specified event(s) instance or events in general.
     * 
     * @param string|object|null $nameOrObject (optional) Event class name or instantiated object.
     * 
     * @return bool
     */
    public function hasEvents(string|object $nameOrObject = null): bool
    {
        if ($nameOrObject === null) {
            return !empty($this->events);
        }

        foreach ($this->events as $event) {
            if ($event instanceof $nameOrObject) {
                return true;
            }
        }

        return false;
    }
}
