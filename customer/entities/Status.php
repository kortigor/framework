<?php

declare(strict_types=1);

namespace customer\entities;

use core\validators\Assert;
use customer\helpers\StatusHelper;

class Status
{
    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 10;

    protected static array $list = [
        self::STATUS_ACTIVE => 'Показывать',
        self::STATUS_INACTIVE => 'Скрыть',
    ];

    /**
     * @var string
     */
    protected string $description;
    /**
     * @var StatusHelper
     */
    protected StatusHelper $helper;

    public function __construct(private int $value)
    {
        Assert::inArray($value, array_keys(static::$list));
        $this->description = static::$list[$value];
    }

    public static function list(): array
    {
        return static::$list;
    }

    public static function description(int $value): string
    {
        return (new self($value))->getDescription();
    }

    public static function isValid(mixed $value): bool
    {
        return in_array($value, array_keys(static::$list), true);
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

    public function getHelper(): StatusHelper
    {
        if (!isset($this->helper)) {
            $this->helper = new StatusHelper($this);
        }
        return $this->helper;
    }

    public function isActive(): bool
    {
        return $this->value === static::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->value === static::STATUS_INACTIVE;
    }
}
