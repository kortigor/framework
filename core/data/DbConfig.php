<?php

declare(strict_types=1);

namespace core\data;

use InvalidArgumentException;
use core\interfaces\ConfigProviderInterface;
use core\base\Status;
use core\exception\InvalidCallException;
use common\models\Setting;
use Illuminate\Database\Eloquent\Collection;

/**
 * Application database storable config.
 */
class DbConfig implements ConfigProviderInterface
{
    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * Constructor.
     * 
     * @param bool $readonly Set config as readonly.
     * @param string $group Group of settings.
     */
    public function __construct(private bool $readonly = true, private string $group = '')
    {
        $query = Setting::where('status', Status::STATUS_ACTIVE);
        if ($this->group) {
            $query->where('group', $this->group);
        }
        $this->collection = $query->get();
    }

    /**
     * Config value getter.
     * 
     * @param mixed $prop
     * 
     * @return string
     * @throws InvalidArgumentException If property record not found.
     */
    public function __get($prop): string
    {
        $record = $this->getRecord($prop);
        return $record->value ?: $record->default;
    }

    /**
     * Config value setter.
     * 
     * @param mixed $prop
     * @param mixed $value
     * 
     * @return void
     * @throws InvalidCallException If config is readonly
     * @throws InvalidArgumentException If property record not found.
     */
    public function __set($prop, $value): void
    {
        if ($this->readonly) {
            throw new InvalidCallException("Unable to set readonly config value");
        }

        $record = $this->getRecord($prop);
        $record->value = $value;
        $record->save();
    }

    /**
     * Get the whole property record.
     * 
     * @param string $prop
     * 
     * @return Setting
     */
    public function record(string $prop): Setting
    {
        return $this->getRecord($prop);
    }

    /**
     * Get the whole config collection.
     * 
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /**
     * {@inheritDoc}
     * 
     * Just in pairs $name=>$value.
     */
    public function toArray(): array
    {
        return $this->collection
            ->mapWithKeys(fn (Setting $item) => [$item->name => $item->value])
            ->toArray();
    }

    /**
     * Get config record.
     * 
     * @param string $prop
     * 
     * @return Setting
     * @throws InvalidArgumentException If property record not found.
     */
    private function getRecord(string $prop): Setting
    {
        $record = $this->collection->first(fn (Setting $item) => $item->name === $prop);
        if ($record === null) {
            throw new InvalidArgumentException("Unknown config parameter {$prop}");
        }
        return $record;
    }
}