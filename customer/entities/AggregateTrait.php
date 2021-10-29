<?php

declare(strict_types=1);

namespace customer\entities;

use core\validators\Assert;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait AggregateTrait
{
    /**
     * Save aggregate based on ActiveRecord via transaction.
     * 
     * @return void
     * @throws Throwable if transaction fails.
     */
    public function saveAggregate(): void
    {
        /** @var \core\orm\ActiveRecord $this */
        $this->getConnection()->transaction(function () {
            /** @var \core\orm\ActiveRecord $this */
            if (!$this->pushWithOriginals()) {
                throw new \Exception(sprintf('Aggregate "%s" push failed', get_class($this)));
            }

            $this->syncPivotRelations();
        });
    }

    /**
     * Delete aggregate based on ActiveRecord via transaction.
     * 
     * @return void
     * @throws Throwable if transaction fails.
     */
    public function deleteAggregate(): void
    {
        /** @var \core\orm\ActiveRecord $this */
        $this->getConnection()->transaction(function () {
            // Call via `parent`, to make possible override model's `delete()` function
            parent::delete();
        });
    }

    /**
     * Get list of 'Many-to-many' relation names to be synchronized when aggregate saves.
     * 
     * This relations use pivot tables, so this relations have to sync to save it.
     * 
     * Relations of 'Many-to-many' type are instances of
     * `\Illuminate\Database\Eloquent\Relations\BelongsToMany` class,
     * and produced by ActiveRecord methods:
     *  - belongsToMany();
     *  - morphToMany();
     *  - morphedByMany().
     * 
     * @return string[] names of ralations to sync.
     * @see belongsToMany()
     * @see morphToMany()
     * @see morphedByMany()
     * @see \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function pivotRelationsToSync(): array
    {
        return [];
    }

    /**
     * Sync all relations using pivot tables.
     * 
     * Using this method you can just add or delete objects in relation's collection,
     * and all relations will be sync by this method called from `saveAggregate()`
     * 
     * @return void
     * @see saveAggregate()
     * @throws \InvalidArgumentException if relation not instance of
     * `\Illuminate\Database\Eloquent\Relations\BelongsToMany` class
     */
    protected function syncPivotRelations(): void
    {
        foreach ($this->pivotRelationsToSync() as $relation) {
            Assert::isInstanceOf($this->$relation(), BelongsToMany::class);
            $keys = [];
            /** @var \Illuminate\Database\Eloquent\Collection $collection */
            $collection = $this->$relation;
            if ($collection->isNotEmpty()) {
                $keyName = $collection->first()->getKeyName();
                $keys = $collection->pluck($keyName)->toArray();
            }

            $this->$relation()->sync($keys);
        }
    }
}
