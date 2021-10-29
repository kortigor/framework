<?php

namespace common\events;

use core\orm\ActiveRecord;
use core\event\BaseEvent;

class EntityBlocked extends BaseEvent implements EntityUpdateEventInterface
{
    const EVENT_NAME = 'Заблокирован';

    private ActiveRecord $model;

    public function __construct(ActiveRecord $model)
    {
        $this->model = $model;
    }

    public function getModel(): ActiveRecord
    {
        return $this->model;
    }
}
