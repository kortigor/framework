<?php

declare(strict_types=1);

namespace customer\services;

use common\services\BaseService;
use customer\entities\Status;
use customer\entities\Setting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Collection;

class SettingService extends BaseService
{
    /**
     * Find Setting by condition
     * 
     * @param array $condition
     * 
     * @return Setting
     * @throws ModelNotFoundException if Setting with given condition is not found
     */
    protected function findByCondition(array $condition): Setting
    {
        return Setting::where($condition)->firstOrFail();
    }

    /**
     * Get Setting by id
     * 
     * @param string $id Setting id
     * 
     * @return Setting
     * @throws ModelNotFoundException if Setting with given id is not exists
     */
    public function get(string $id): Setting
    {
        return $this->findByCondition([['id', $id]]);
    }

    /**
     * Get only active Setting by id
     * 
     * @param string $id Setting id
     * 
     * @return Setting
     * @throws ModelNotFoundException if active Setting with given id is not exists
     */
    public function getActive(string $id): Setting
    {
        return $this->findByCondition([
            ['id', $id],
            ['status', Status::STATUS_ACTIVE]
        ]);
    }

    /**
     * Get settings group(s).
     * 
     * @param string|array $group Name or array of names of groups to get
     * 
     * @return Collection
     */
    public function getGroup(string|array $group): Collection
    {
        return Setting::where('status', Status::STATUS_ACTIVE)
            ->whereIn('group', (array) $group)
            ->orderBy('order')
            ->get();
    }
}