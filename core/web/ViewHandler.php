<?php

declare(strict_types=1);

namespace core\web;

use core\interfaces\ViewHandlerInterface;

abstract class ViewHandler implements ViewHandlerInterface
{
    /**
     * @var string
     */
    protected string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * {@inheritDoc}
     */
    abstract function handle(string $content): string;
}