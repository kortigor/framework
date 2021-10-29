<?php

declare(strict_types=1);

namespace core\base;

use core\validators\Assert;

class Status
{
    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 10;

    protected static array $list = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    /**
     * @var int
     */
    protected int $value;
    /**
     * @var string
     */
    protected string $description;

    public function __construct(int $value)
    {
        Assert::inArray($value, array_keys(static::$list));
        $this->value = $value;
        $this->description = static::$list[$value];
    }

    public static function list(): array
    {
        return static::$list;
    }

    public function __toString()
    {
        return $this->description;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->value === static::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->value === static::STATUS_INACTIVE;
    }
}