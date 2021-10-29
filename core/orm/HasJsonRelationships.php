<?php

declare(strict_types=1);

namespace core\orm;

use Staudenmeir\EloquentJsonRelations\HasJsonRelationships as HasJsonRelationshipsVendor;

trait HasJsonRelationships
{
    use HasJsonRelationshipsVendor;

    /**
     * Get an attribute from the model.
     * 
     * Modified by Kort to be able to use mutated or casted json attributes in relations.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $attribute = preg_split('/(->|\[\])/', $key)[0];

        // if (array_key_exists($attribute, $this->attributes)) {
        //     return $this->getAttributeValue($key);
        // }

        if (
            array_key_exists($attribute, $this->attributes)
            || array_key_exists($attribute, $this->casts)
            || $this->hasGetMutator($attribute)
            || $this->isClassCastable($attribute)
        ) {
            return $this->getAttributeValue($key);
        }

        return parent::getAttribute($key);
    }
}