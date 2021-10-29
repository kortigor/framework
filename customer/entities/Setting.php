<?php

declare(strict_types=1);

namespace customer\entities;

/**
 * @property Status $status Status
 */
final class Setting extends \common\models\Setting
{
    use AggregateTraitStatus;

    protected $casts = [
        'status' => casts\SerializeImmutable::class,
    ];

    public function rules(): array
    {
        return [
            [['name', 'type'], 'required'],
            ['status', 'oneOf' => [array_keys(Status::list())]],
            ['name', 'uniqueAttribute' => ['name', $this]],
        ];
    }

    public static function buildEmpty(): self
    {
        $new = new self();
        $new->status = Status::STATUS_ACTIVE;
        return $new;
    }
}
