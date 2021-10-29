<?php

declare(strict_types=1);

namespace customer\models\forms;

use customer\entities\Status;

trait FormStatusListsTrait
{
    /**
     * Available statuses list.
     * 
     * @return array
     */
    public function statusList(): array
    {
        return Status::list();
    }
}
