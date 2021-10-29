<?php

declare(strict_types=1);

namespace core\event;

/**
 * Provider is a listener provider that registers event listeners for specified class names used as events
 * and gives out a list of handlers by event interface provided for further use with Dispatcher.
 *
 * ```php
 * $provider = new ProviderPrioritized;
 * 
 * $provider->addListener(AfterDocumentProcessed::class, function ($event) {
 *    $document = $event->getDocument();
 *    // do something with document
 * }, 100);
 * 
 * // This listener will be executed first
 * $provider->addListener(BeforeDocumentProcessed::class, function ($event) {
 *    $document = $event->getDocument();
 *    // do something with document
 * }, 200);
 *
 * ```
 */
final class ProviderPrioritized  implements ListenerProviderInterface
{
    protected array $listeners = [];

    public function getListenersForEvent(object $event): iterable
    {
        yield from $this->getForEvents(get_class($event));
        yield from $this->getForEvents(...array_values(class_parents($event)));
        yield from $this->getForEvents(...array_values(class_implements($event)));
    }

    /**
     * Attaches listener to corresponding event based on event class name and with priority.
     * 
     * Method signature can be the following:
     * ```
     *  function ($event): void;
     * ```
     * Or:
     * ```
     *  function (): void;
     * ```
     * @param string $eventClassName event class name to listen.
     * @param callable $listener
     * @param int $priority listener priority, larger value means higher priority.
     * 
     * @return self
     */
    public function addListener(string $eventClassName, callable $listener, int $priority = 1): self
    {
        $this->listeners[$priority][$eventClassName][] = $listener;
        return $this;
    }

    /**
     * Remove listeners to corresponding event based on the class name.
     *
     * @param string $eventClassName event class name to remove
     * @return self
     */
    public function removeListener(string $eventClassName): self
    {
        foreach ($this->listeners as $priority) {
            unset($this->listeners[$priority][$eventClassName]);
        }

        return $this;
    }

    /**
     * Clear all listeners
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->listeners = [];
        return $this;
    }

    /**
     * @param string ...$eventClassNames
     * @return iterable[callable]
     */
    private function getForEvents(string ...$eventClassNames): iterable
    {
        $priorities = array_keys($this->listeners);
        usort($priorities, function ($a, $b) {
            return $b <=> $a;
        });

        foreach ($eventClassNames as $eventClassName) {
            foreach ($priorities as $priority) {
                foreach ($this->listeners[$priority] as $listenerClassName => $listeners) {
                    if ($listenerClassName == $eventClassName) {
                        foreach ($listeners as $listener) {
                            yield $listener;
                        }
                    }
                }
            }
        }
    }
}
