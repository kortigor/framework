<?php

declare(strict_types=1);

namespace core\event;

use InvalidArgumentException;

/**
 * Simple event management implementation.
 */
class Manager implements EventStorableInterface
{
    const STANDARD_SOURCE = '@STANDARD_EVENT_SOURCE';

    /**
     * @var array<string, EventsDispatcherInterface[]> Dispatchers storage.
     * 
     * Associative array where keys are source ids and values is array of stored dispatchers.
     */
    protected array $dispatchers = [];

    /**
     * @var array<string, object[]> Events storage.
     * 
     * Associative array where keys are source ids and values is array of stored events.
     */
    protected array $events = [];

    /**
     * Add listener provider.
     * 
     * @param ListenerProviderInterface $provider Listenet provider object.
     * @param string $source (optional) Source id.
     * 
     * @return self
     */
    public function addProvider(ListenerProviderInterface $provider, string $source = ''): self
    {
        $dispatcher = new Dispatcher($provider);
        $this->dispatchers[$this->getSource($source)][] = $dispatcher;

        return $this;
    }

    /**
     * Dispatch single event.
     * 
     * @param object $event Event object.
     * @param string $source (optional) Source id.
     * 
     * @return object
     * @throws InvalidArgumentException if no dispatcher with given source.
     */
    public function dispatch(object $event, string $source = ''): object
    {
        $dispatchers = $this->getDispatchers($source);

        /** @var EventsDispatcherInterface $dispatcher */
        foreach ($dispatchers as $dispatcher) {
            $dispatcher->dispatch($event);
        }

        return $event;
    }

    /**
     * Dispatch many events.
     * 
     * @param iterable $events Events iterable collection.
     * @param string $source (optional) Source id.
     * 
     * @return iterable
     * @throws InvalidArgumentException if no dispatcher with given source.
     */
    public function dispatchAll(iterable $events, string $source = ''): iterable
    {
        $dispatchers = $this->getDispatchers($source);

        /** @var EventsDispatcherInterface $dispatcher */
        foreach ($dispatchers as $dispatcher) {
            $dispatcher->dispatchAll($events);
        }

        return $events;
    }

    /**
     * Clear source.
     * 
     * @param string $source (optional) Source id to clear.
     * Clear all sources if not specified.
     * 
     * @return self
     */
    public function clear(string $source = ''): self
    {
        if ($source) {
            unset($this->dispatchers[$source], $this->events[$source]);
        } else {
            $this->dispatchers = [];
            $this->events = [];
        }

        return $this;
    }

    /**
     * Store event inside event manager.
     * 
     * @param object $event Event object to record.
     * @param string $source (optional) Source id.
     * 
     * @return self
     */
    public function recordEvent(object $event, string $source = ''): self
    {
        $this->events[$this->getSource($source)][] = $event;

        return $this;
    }

    /**
     * Store collection of events inside manager.
     * 
     * @param object[]|EventStorableInterface $collection Collection of events OR entity with storable events.
     * @param string $source (optional) Source id.
     * 
     * @return self
     */
    public function recordCollection(iterable|EventStorableInterface $collection, string $source = ''): self
    {
        $events = $collection instanceof EventStorableInterface ? $collection->releaseEvents() : $collection;

        foreach ($events as $event) {
            $this->recordEvent($event, $source);
        }

        return $this;
    }

    /**
     * Remove all event instances from manager.
     * 
     * @param object $event Event object to remove.
     * 
     * @return int Number of removed events.
     */
    public function removeEvent(object $event): int
    {
        $instance = get_class($event);
        $i = 0;

        foreach ($this->getEventSources() as $source) {
            foreach ($this->events[$source] as $key => $ev) {
                if ($ev instanceof $instance) {
                    unset($this->events[$source][$key]);
                    $i++;
                }
            }
        }

        return $i;
    }

    /**
     * Retrieve and clear stored events from given source.
     * 
     * @param string $source (optional) Source id.
     * 
     * @return object[] Collection of event objects.
     */
    public function releaseSource(string $source = ''): array
    {
        $source = $this->getSource($source);
        $events = $this->events[$source];
        unset($this->events[$source]);

        return $events;
    }

    /**
     * Retrieve and clear stored events from all sources.
     * 
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $events = [];
        foreach ($this->getEventSources() as $source) {
            foreach ($this->releaseSource($source) as $event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Get all available event sources.
     * 
     * @return string[] List of all currently available sources ids.
     */
    public function getEventSources(): array
    {
        return array_keys($this->events);
    }

    /**
     * Get specified (if not empty) or standard source.
     * 
     * @param string $source Source id.
     * 
     * @return string Specified or standard source id.
     */
    private function getSource(string $source): string
    {
        return $source ?: static::STANDARD_SOURCE;
    }

    /**
     * Get dispatchers for specified source (if not empty) or from standard source.
     * 
     * @param string $source Source id.
     * 
     * @return EventsDispatcherInterface[]
     * @throws InvalidArgumentException if dispatcher(s) in specified source does not exists.
     */
    private function getDispatchers(string $source): array
    {
        $source = $this->getSource($source);
        if ($source === self::STANDARD_SOURCE) {
            return $this->dispatchers[$source] ?? [];
        }

        if (!isset($this->dispatchers[$source])) {
            throw new InvalidArgumentException("Event provider with id [{$source}] not registered.");
        }

        return $this->dispatchers[$source];
    }
}