<?php

declare(strict_types=1);

namespace customer\helpers;

use customer\entities\Status;

final class StatusHelper
{
    public function __construct(private Status $status)
    {
    }

    public function blockButtonText(array $values = null): string
    {
        $on = $values[0] ?? 'Скрыть';
        $off = $values[1] ?? 'Показать';

        return $this->status->isActive()
            ? '<i class="fas fa-fw fa-eye-slash"></i> ' . $on
            : '<i class="fas fa-fw fa-eye"></i> ' . $off;
    }

    public function blockConfirmQuestion(array $values = null): string
    {
        $on = $values[0] ?? 'cкрыть';
        $off = $values[1] ?? 'показать';

        return $this->status->isActive()
            ? "Действительно {$on}?"
            : "Действительно {$off}?";
    }

    public function blockQueryValue(): string
    {
        return $this->status->isActive() ? 'block' : 'unblock';
    }

    public function statusLabel(): string
    {
        return $this->status->isActive()
            ? '<span class="badge badge-success">' . $this->status . '</span>'
            : '<span class="badge badge-danger">' . $this->status . '</span>';
    }
}
