<?php

declare(strict_types=1);

namespace customer\entities\casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * This casting need to use in cases if attribute have mutator,
 * but we need immutable attribute value when model serialized
 * i.e. via `toArray()` or `toJson()` methods.
 * 
 * Note: Without attribute mutator this cast does nothing and makes no sense.
 * 
 * @see https://laravel.com/docs/8.x/eloquent-mutators#custom-casts
 */
class SerializeImmutable implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return $value;
    }

    public function set($model, $key, $value, $attributes)
    {
        return [
            $key => $value,
        ];
    }
}
