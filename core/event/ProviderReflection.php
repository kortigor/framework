<?php

declare(strict_types=1);

namespace core\event;

/**
 * Provider is a listener provider that registers event listeners for interfaces used in callable type-hints
 * and gives out a list of handlers by event interface provided for further use with Dispatcher.
 *
 * ```php
 * $provider = new ProviderReflection;
 * 
 * $provider->addListener(function (AfterDocumentProcessed $event) {
 *    $document = $event->getDocument();
 *    // do something with document
 * });
 * 
 * //or
 * 
 * $provider->addListener(function () {
 *    // do something
 * }, get_class($event));
 * ```
 */
final class ProviderReflection implements ListenerProviderInterface
{
    protected array $listeners = [];

    public function getListenersForEvent(object $event): iterable
    {
        yield from $this->getForEvents(get_class($event));
        yield from $this->getForEvents(...array_values(class_parents($event)));
        yield from $this->getForEvents(...array_values(class_implements($event)));
    }

    /**
     * Attaches listener to corresponding event based on the type-hint used for the event argument.
     *
     * Method signature generally should be the following:
     * ```
     *  function (MyEvent $event): void;
     * ```
     * In case if `$eventClassName` specified, signature can be the following:
     * ```
     *  function (): void;
     * ```
     *
     * Any callable could be used be it a closure, invokable object or array referencing a class or object.
     * The following are some examples:
     * ```
     * function ($event) { ... }         // anonymous function
     * [$object, 'handleClick']          // $object->handleClick()
     * ['Page', 'handleClick']           // Page::handleClick()
     * 'handleClick'                     // global function handleClick()
     * ```
     *
     * @param callable $listener
     * @param string|null $eventClassName
     * @return self
     */
    public function addListener(callable $listener, string $eventClassName = null): self
    {
        $eventClassName ??= $this->getParameterType($listener);
        $this->listeners[$eventClassName][] = $listener;
        return $this;
    }

    /**
     * Helper to register all public (including static) methods from given object as listeners.
     * 
     * @param object $objectListeners instance of listeners class.
     * 
     * @return self
     */
    public function addObjectListeners(object $objectListeners): self
    {
        $reflection = new \ReflectionObject($objectListeners);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->isConstructor()) {
                continue;
            }
            $this->addListener([$objectListeners, $method->getName()]);
        }

        return $this;
    }

    /**
     * Helper to register all public static methods from given class name as listeners.
     * 
     * @param string $className name of class contains static listeners.
     * 
     * @return self
     */
    public function addClassListeners(string $className): self
    {
        // This try-catch is only here to be happy about uncaught reflection exceptions.
        try {
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                sprintf('Trying to register listeners class "%s" that not exists.', $className),
                0,
                $e
            );
        }

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->isStatic()) {
                $this->addListener([$className, $method->getName()]);
            }
        }

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
        unset($this->listeners[$eventClassName]);
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
        foreach ($eventClassNames as $eventClassName) {
            if (isset($this->listeners[$eventClassName])) {
                yield from $this->listeners[$eventClassName];
            }
        }
    }

    /**
     * Derives the interface type of the first argument of a callable.
     *
     * @param callable $callable The callable for which we want the parameter type.
     * @return string The interface the parameter is type hinted on.
     */
    private function getParameterType(callable $callable): string
    {
        // This try-catch is only here to keep listeners happy about uncaught reflection exceptions.
        try {
            $closure = new \ReflectionFunction(\Closure::fromCallable($callable));
            $params = $closure->getParameters();

            $reflectedType = isset($params[0]) ? $params[0]->getType() : null;
            if ($reflectedType === null) {
                throw new \InvalidArgumentException('Listeners must declare an object type they can accept.');
            }
            /** @var ReflectionType $reflectedType */
            $type = $reflectedType->getName(); // getName() is undocumented https://www.php.net/manual/ru/class.reflectiontype.php#124658
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering listener.', 0, $e);
        }

        return $type;
    }
}