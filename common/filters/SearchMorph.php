<?php

declare(strict_types=1);

namespace common\filters;

use utils\stemmer\LinguaStemRu;

class SearchMorph extends Search
{
    /**
     * @var LinguaStemRu
     */
    protected LinguaStemRu $stemmer;

    /**
     * {@inheritDoc}
     */
    public function build(string|array $value)
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        $words = explode(' ', $value);
        foreach ($words as $word) {
            $word = $this->getStemmer()->stemWord($word);
            if (mb_strlen($word) >= 3) {
                $this->builder->whereLike($this->fields, $word);
            }
        }
    }

    protected function getStemmer(): LinguaStemRu
    {
        if (!isset($this->stemmer)) {
            $this->stemmer = new LinguaStemRu;
        }
        return $this->stemmer;
    }
}