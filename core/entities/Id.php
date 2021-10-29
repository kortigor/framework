<?php

declare(strict_types=1);

namespace core\entities;

use core\validators\Assert;
use Ramsey\Uuid\Uuid;

class Id
{
    /**
     * Constructor.
     * 
     * @param string $id Id value
     */
    public function __construct(private string $id)
    {
        Assert::uuid($id);
        $this->id = $id;
    }

    /**
     * Get new Id object
     * 
     * @return self
     */
    public static function next(): self
    {
        return new self(Uuid::uuid6()->toString());
    }

    /**
     * Get id value
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check two ids is equal.
     * 
     * @param self $other Other id to compare, instance of Id
     * 
     * @return bool True if ids is equal
     */
    public function isEqualTo(self $other): bool
    {
        return $this->getId() === $other->getId();
    }
}