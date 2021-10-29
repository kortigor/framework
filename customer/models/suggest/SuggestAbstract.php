<?php

declare(strict_types=1);

namespace customer\models\suggest;

use core\exception\InvalidConfigException;
use common\filters\Search;
use Illuminate\Database\Eloquent\Builder;

abstract class SuggestAbstract
{
    /**
     * @var string|callable[] Fields to search by QueryFilter. Can be in dot format, like 'translate.name'
     */
    protected array $fields = [];

    /**
     * Constructor
     * 
     * @param Search $filter Model filter
     * @param string|callable[] $fields Fields to search in. Can be in dot format, like 'translate.name'
     */
    public function __construct(protected Search $filter, array $fields = [])
    {
        if ($fields) {
            $this->fields = $fields;
        }

        if ($this->fields) {
            $this->filter->fields = $this->fields;
        }
    }

    /**
     * Build ActiveRecord query
     * 
     * @return Builder
     */
    abstract public function prepare(): Builder;

    /**
     * Get normalized attributes to use with `\core\orm\Search`
     * 
     * @return array
     * 
     * @see \core\orm\Search
     */
    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->fields as $field) {
            if (!is_string($field) && !is_callable($field)) {
                throw new InvalidConfigException("Field definition must be string or callable" . gettype($field) . " given");
            }

            if (is_string($field) && str_contains($field, '.')) {
                $exp = explode('.', $field);
                $attributes[] = end($exp);
            } else {
                $attributes[] = $field;
            }
        }

        return $attributes;
    }
}
