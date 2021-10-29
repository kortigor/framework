<?php

declare(strict_types=1);

namespace common\filters;

use core\orm\QueryFilterConfigurable;

class Search extends QueryFilterConfigurable
{
    public array $parameters = ['search'];

    /**
     * Build search filtering query
     * 
     * @param string $text
     */
    public function build(string|array $value)
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        $this->builder->whereLike($this->fields, $value);
    }
}