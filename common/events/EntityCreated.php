<?php

namespace common\events;

use core\event\BaseEvent;
use core\orm\ActiveRecord;

class EntityCreated extends BaseEvent implements EntityUpdateEventInterface
{
    const EVENT_NAME = 'Создан';

    public function __construct(private ActiveRecord $model)
    {
    }

    public function getModel(): ActiveRecord
    {
        return $this->model;
    }
}
