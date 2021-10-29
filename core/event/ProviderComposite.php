<?php

declare(strict_types=1);

namespace core\event;

/**
 * ProviderComposite is a listener provider that allows combining multiple listener providers.
 */
class ProviderComposite implements ListenerProviderInterface
{
    /**
     * @var ListenerProviderInterface[]
     */
    private array $providers = [];

    /**
     * {@inheritdoc}
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->providers as $provider) {
            yield from $provider->getListenersForEvent($event);
        }
    }

    /**
     * Attach listeners provider
     * 
     * @param ListenerProviderInterface $provider
     * 
     * @return self
     */
    public function attachProvider(ListenerProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        return $this;
    }
}
