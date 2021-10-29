<?php

declare(strict_types=1);

namespace core\web;

use core\collections\ArrayObjectDefault;

abstract class Widget
{
    /**
     * @var View
     */
    protected View $view;

    /**
     * @var ArrayObjectDefault Widget data.
     */
    protected ArrayObjectDefault $data;

    public function __construct(View $view, array $data = [])
    {
        $this->view = $view;
        $this->data = new ArrayObjectDefault($data);
    }

    /**
     * Execute widget.
     * 
     * @return string Content rendered by widget.
     */
    abstract public function execute(): string;
}