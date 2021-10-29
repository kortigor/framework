<?php

declare(strict_types=1);

namespace core\link;

use Stringable;
use Psr\Link\EvolvableLinkInterface;

class Link implements EvolvableLinkInterface, Relations
{
    // Extra relations

    /**
     * @var string
     * 
     * @see https://mercure.rocks/spec
     */
    const REL_MERCURE = 'mercure';

    /**
     * @var string[]
     */
    private array $rels = [];

    /**
     * Constructor
     * 
     * @param string $rel Link relation
     * @param string $href Link href attribute
     * @param array<string, string|bool|string[]> $attributes Other link attributes
     */
    public function __construct(string $rel = '', private string $href = '', private array $attributes = [])
    {
        if ($rel) {
            $this->rels[$rel] = $rel;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * {@inheritdoc}
     */
    public function isTemplated(): bool
    {
        return $this->hrefIsTemplated($this->href);
    }

    /**
     * {@inheritdoc}
     */
    public function getRels(): array
    {
        return array_values($this->rels);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function withHref(string|Stringable $href): static
    {
        $that = clone $this;
        $that->href = $href;

        return $that;
    }

    /**
     * {@inheritdoc}
     */
    public function withRel(string $rel): static
    {
        $that = clone $this;
        $that->rels[$rel] = $rel;

        return $that;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutRel(string $rel): static
    {
        $that = clone $this;
        unset($that->rels[$rel]);

        return $that;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute(string $attribute, string|Stringable|int|float|bool|array $value): static
    {
        $that = clone $this;
        $that->attributes[$attribute] = $value;

        return $that;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute(string $attribute): static
    {
        $that = clone $this;
        unset($that->attributes[$attribute]);

        return $that;
    }

    /**
     * Perform check whether link is templated
     * 
     * @param string $href
     * 
     * @return bool
     */
    private function hrefIsTemplated(string $href): bool
    {
        return str_contains($href, '{') || str_contains($href, '}');
    }
}