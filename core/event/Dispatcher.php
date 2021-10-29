<?php

declare(strict_types=1);

namespace core\event;

/**
 * Dispatcher executes listeners attached to event passed
 * @see https://www.php-fig.org/psr/psr-14/
 */
class Dispatcher implements EventsDispatcherInterface
{
    public function __construct(private ListenerProviderInterface $listenerProvider)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                return $event;
            }
            $listener($event);
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchAll(iterable $events): iterable
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }

        return $events;
    }
}
