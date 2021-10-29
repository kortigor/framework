<?php

declare(strict_types=1);

namespace core\link;

use core\interfaces\SerializerInterface;
use Psr\Link\LinkInterface;

/**
 * JSON Hypermedia API Language serializer.
 * 
 * @see https://tools.ietf.org/html/draft-kelly-json-hal-03
 */
final class HalSerializer implements SerializerInterface
{
    /**
     * Serializes a list of links into proper array format.
     * 
     * @param @param string|LinkInterface[]|\Traversable $links the links to be serialized
     * @return array the proper array representation of the links.
     */
    public function serialize(iterable $links): array
    {
        $elements = [];
        foreach ($links as $link) {
            $elements = array_merge($elements, $this->serializeLink($link));
        }

        return $elements;
    }

    /**
     * @param LinkInterface $link
     * 
     * @return array
     */
    private function serializeLink(LinkInterface $link): array
    {
        $elements = [];
        foreach ($link->getRels() as $rel) {
            $elements[$rel] = $link->getHref();
        }

        return $elements;
    }
}