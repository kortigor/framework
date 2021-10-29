<?php

declare(strict_types=1);

namespace customer\entities;

use ReflectionClass;
use core\validators\Assert;

/**
 * Employee role implementation.
 */
final class EmployeeRole
{
    // Employees roles: 100 - 199
    const CONTENT_MANAGER   = 110; // Contend manager
    const ADMINISTRATOR     = 199; // Administrator

    // User|Client roles: 200 - 299
    const USER              = 200; // Registered user
    const CLIENT            = 201; // Client

    private const LIST = [
        self::CONTENT_MANAGER   => 'Контент менеджер',
        self::ADMINISTRATOR     => 'Администратор',
        self::USER              => 'Зарегистрированный пользователь',
        self::CLIENT            => 'Клиент',
    ];

    /**
     * @var string
     */
    private string $description;

    /**
     * Constructor.
     * 
     * @param int $value Role code.
     */
    public function __construct(private int $value)
    {
        Assert::inArray($this->value, array_keys(self::LIST));
        $this->description = self::LIST[$value];
    }

    public static function list(): array
    {
        return self::LIST;
    }

    public static function roles(): array
    {
        $reflection = new ReflectionClass(static::class);
        return $reflection->getConstants();
    }

    public static function description(int $value): string
    {
        return (new self($value))->getDescription();
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

    public function isAdmin(): bool
    {
        return $this->value === self::ADMINISTRATOR;
    }

    public function isContentManager(): bool
    {
        return $this->value === self::CONTENT_MANAGER;
    }

    public function isUser(): bool
    {
        return $this->value === self::USER;
    }

    public function isClient(): bool
    {
        return $this->value === self::CLIENT;
    }
}
