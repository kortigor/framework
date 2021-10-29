<?php

namespace customer\entities;

/**
 * History trackable interface
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection $history Collection of EntityHistory objects
 */
interface HistoryTrackedInterface
{
    /**
     * Entity history relation
     * 
     * @return mixed
     */
    public function history();
}
