<?php

declare(strict_types=1);

namespace core\interfaces;

use core\validators\ValidatorCollection;
use core\validators\NormalizatorCollection;

interface ModelValidableInterface
{
    /**
     * The name of the default scenario.
     */
    const SCENARIO_DEFAULT = 'default';

    public function rules(): array;

    public function normalizators(): array;

    public function scenarios(): array;

    public function getScenario(): string;

    public function setScenario(string $value): self;

    public function normalize(): void;

    public function beforeValidate(): bool;

    public function afterValidate();

    public function validate(string|array $attributeNames, bool $clearErrors): bool;

    public function addRule(string $attribute, array $validator, array $options): self;

    public function addNormalizator(string $attribute, string $normalizator): self;


    public function addError(string $attribute, string $message, string $validator): void;

    public function getErrorSummary(bool $showAllErrors): array;

    public function getErrorMessagesOnly(array $errors): array;

    public function getErrors(string $attribute): array;

    public function getFullErrors(string $attribute): array;

    public function getFirstErrors(): array;

    public function getFirstError(string $attribute): ?string;

    public function hasErrors(string $attribute): bool;

    public function clearErrors(string $attribute): void;

    public function getValidators(): ValidatorCollection;

    public function getNormalizators(): NormalizatorCollection;


    public function activeAttributes(): array;

    public function isAttributeActive(string $attribute): bool;

    public function getActiveValidators(string $attribute): array;

    public function isAttributeRequired(string $attribute): bool;

    public function getAttributeLabel(string $attribute): string;

    public function getAttributeHint(string $attribute): string;

    public function attributeLabels(): array;

    public function attributeHints(): array;

    public function generateAttributeLabel(string $name): string;
}
