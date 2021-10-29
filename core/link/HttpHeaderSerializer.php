<?php

declare(strict_types=1);

namespace core\link;

use core\interfaces\SerializerInterface;
use Psr\Link\LinkInterface;

/**
 * Serializes a list of Link instances to an HTTP Link header.
 *
 * @see https://tools.ietf.org/html/rfc5988
 */
final class HttpHeaderSerializer implements SerializerInterface
{
    /**
     * Builds the value of the "Link" HTTP header.
     *
     * @param LinkInterface[]|\Traversable $links
     */
    public function serialize(iterable $links): ?string
    {
        $elements = [];
        foreach ($links as $link) {
            if ($element = $this->extract($link)) {
                $elements[] = $element;
            }
        }

        return $elements ? implode(',', $elements) : null;
    }

    private function extract(LinkInterface $link): ?string
    {
        if ($link->isTemplated()) {
            return null;
        }

        $parts = $this->extractRel($link);
        foreach ($link->getAttributes() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = $this->extractAttribute($key, $v);
                }
                continue;
            }

            if (!is_bool($value)) {
                $parts[] = $this->extractAttribute($key, $value);
                continue;
            }

            if ($value === true) {
                $parts[] = $key;
            }
        }

        return sprintf('<%s>%s', $link->getHref(), implode('; ', $parts));
    }

    private function extractAttribute(string $name, string $value): string
    {
        return sprintf('%s="%s"', $name, preg_replace('/(?<!\\\\)"/', '\"', $value));
    }

    private function extractRel(LinkInterface $link): array
    {
        return ['', sprintf('rel="%s"', implode(' ', $link->getRels()))];
    }
}