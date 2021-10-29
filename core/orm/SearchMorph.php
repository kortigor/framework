<?php

declare(strict_types=1);

namespace core\orm;

use utils\stemmer\LinguaStemRu;

/**
 * Search ActiveRecord model attributes using stemmer.
 */
class SearchMorph extends Search
{
    /**
     * @var LinguaStemRu
     */
    private LinguaStemRu $stemmer;

    /**
     * Constructor.
     * 
     * @param ActiveRecord $model Model to search in.
     */
    public function __construct(protected ActiveRecord $model)
    {
        $this->stemmer = new LinguaStemRu;
    }

    /**
     * Search string entry in attribute value using stemmer
     * 
     * @param string $value
     * @param string $search
     * 
     * @return bool
     */
    protected function stemSearch(string $value, string $search): bool
    {
        $words = explode(' ', $search);
        foreach ($words as $word) {
            $word = $this->stemmer->stemWord($word);
            if (mb_strlen($word) >= 3) {
                if ($this->simpleSearch($value, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find string in attribute value depends of stemmer usage.
     * 
     * @param string $value
     * @param string $search
     * 
     * @return bool
     */
    protected function find(string|int|float $value, string $search): bool
    {
        $value = (string) $value;
        return $this->stemSearch($value, $search);
    }
}