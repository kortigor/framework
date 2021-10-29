<?php

namespace common\events;

use core\orm\ActiveRecord;

interface EntityUpdateEventInterface
{
    public function getModel(): ActiveRecord;
}