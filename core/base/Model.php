<?php

declare(strict_types=1);

namespace core\base;

use core\interfaces\ArrayableInterface;
use core\interfaces\ModelValidableInterface;
use core\traits\ModelFillTrait;
use core\traits\ModelValidateTrait;
use core\traits\ArrayableTrait;
use core\traits\GetSetByPropsTrait;

abstract class Model implements ArrayableInterface, ModelValidableInterface
{
    use ModelFillTrait;
    use ModelValidateTrait;
    use ArrayableTrait;
    use GetSetByPropsTrait;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Model initialization.
     * 
     * @return void
     */
    public function init(): void
    {
    }
}