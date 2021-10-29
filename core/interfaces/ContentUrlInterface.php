<?php

declare(strict_types=1);

namespace core\interfaces;

/**
 * ContentUrlInterface is the interface that should be implemented by a class providing content url options.
 */
interface ContentUrlInterface
{
    /**
     * Get options for Url generator.
     * Method should return array of options valid for UrlGenerator.
     * 
     * @return array
     * @see \core\routing\UrlGenerator
     * @see \core\helpers\Url::to()
     */
    public function getUrlOptions(): array;
}
