<?php

declare(strict_types=1);

namespace core\orm;

use core\interfaces\OrmInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class EloquentOrm extends Capsule implements OrmInterface
{
    public function __construct(Config $config)
    {
        parent::__construct();
        $this->addConnection((array) $config);
        $this->setEventDispatcher(new Dispatcher(new Container));
        $this->setAsGlobal();
        $this->bootEloquent();
    }
}