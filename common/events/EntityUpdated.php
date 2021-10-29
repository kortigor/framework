<?php

namespace common\events;

use core\event\BaseEvent;
use core\orm\ActiveRecord;

class EntityUpdated extends BaseEvent implements EntityUpdateEventInterface
{
    const EVENT_NAME = 'Изменен';

    public function __construct(private ActiveRecord $model)
    {
    }

    public function getModel(): ActiveRecord
    {
        return $this->model;
    }
}
