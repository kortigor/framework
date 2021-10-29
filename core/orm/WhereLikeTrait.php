<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Database\Eloquent\Builder;

/**
 * Add `whereLike` macro to ActiveRecord model
 * @see https://freek.dev/1182-searching-models-using-a-where-like-query-in-laravel
 * 
 * @author Kort <kort.igor@gmail.com> Ability to search in JSON casted fields
 */
trait WhereLikeTrait
{
    protected static function bootWhereLikeTrait()
    {
        Builder::macro('whereLike', function (array $attributes, string $searchTerm) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $buffer = explode('.', $attribute);
                            $attributeField = array_pop($buffer);
                            $relationPath = implode('.', $buffer);
                            $query->orWhereHas($relationPath, function (Builder $query) use ($attributeField, $searchTerm) {
                                if ($query->getModel()->isJsonCastableWhereLike($attributeField)) {
                                    // JSON field case insensitive fu*kin search
                                    $query->whereRaw('LOWER(' . $attributeField . ') LIKE ?', ["%" . mb_strtolower($searchTerm) . "%"]);
                                } else {
                                    $query->where($attributeField, 'LIKE', "%{$searchTerm}%");
                                }
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            if ($query->getModel()->isJsonCastableWhereLike($attribute)) {
                                // JSON field case insensitive fu*kin search
                                $query->orWhereRaw('LOWER(' . $attribute . ') LIKE ?', ["%" . mb_strtolower($searchTerm) . "%"]);
                            } else {
                                $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                            }
                        }
                    );
                }
            });
            return $this;
        });
    }

    /**
     * Helper method to check json castable field.
     * Wrapper to run model's protected method `HasAttributes::isJsonCastable()`
     * 
     * @param string $key
     * 
     * @return bool
     * 
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes::isJsonCastable()
     */
    public function isJsonCastableWhereLike(string $key): bool
    {
        return $this->isJsonCastable($key);
    }
}