<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Pagination\Paginator;

class PageResolver
{
    /**
     * Set paginator page resolver by $_GET request query parameter.
     * 
     * @param string $name Query parameter name.
     * 
     * @return void
     */
    public static function byGetQueryParameter(string $name): void
    {
        $page = (int) filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
        $resolver = fn () => $page > 0 ? $page : null;
        Paginator::currentPageResolver($resolver);
    }
}