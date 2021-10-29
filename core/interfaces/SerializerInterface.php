<?php

declare(strict_types=1);

namespace core\interfaces;

/**
 * Serializer interface.
 */
interface SerializerInterface
{
	/**
	 * Serialize items.
	 * 
	 * @param iterable $items
	 * 
	 * @return mixed
	 */
	public function serialize(iterable $items);
}