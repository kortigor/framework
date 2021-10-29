<?php

declare(strict_types=1);

namespace customer\helpers;

use ReflectionClass;
use InvalidArgumentException;
use core\orm\ActiveRecord;
use core\orm\QueryFilter;

/**
 * Calculate numbers of entities in every status presented in `Status` class.
 * 
 * @property-read int $STATUS_ACTIVE Number of active entities.
 * @property-read int $STATUS_INACTIVE Number blocked entities.
 * @property-read int $total Number of entities in all statuses.
 */
final class StatusProfileCounter
{
    /**
     * @var array Array of names and values of all defined constants in `Status` class:
     * [ STATUS_ACTIVE => 1, STATUS_INACTIVE => 10, ... ]
     */
    private array $map;

    /**
     * @var array
     */
    private array $cntStatus;

    /**
     * @var int Total number of entities in all statuses.
     */
    private int $cntTotal;

    /**
     * @var bool Whether counting was processed and values actual.
     */
    private bool $isProcessed = false;

    /**
     * @var QueryFilter[]
     */
    private array $filters = [];

    /**
     * Constructor.
     * 
     * @param string $class Entity class name.
     * @param string $statusClass Class contains definitions of 'STATUS_*' constants.
     * @throws InvalidArgumentException If given entity class not ActiveRecord or have no 'status' attribute.
     */
    public function __construct(private string $class, private string $statusClass = \customer\entities\Status::class)
    {
        $parents = class_parents($class);
        if (!$parents || !in_array(ActiveRecord::class, $parents) || !$class::hasAttribute('status')) {
            throw new InvalidArgumentException('To count statuses, model MUST be ActiveRecord and have "status" attribute');
        }

        $this->createMap();
        $this->reset();
    }

    public function __get($name): int
    {
        if (!$this->isProcessed) {
            $this->process();
        }

        return match ($name) {
            'total' => $this->cntTotal,
            default => $this->cntStatus[$name] ?? 0
        };
    }

    /**
     * Add records filter.
     * 
     * @param QueryFilter $filter Filter to exclude records.
     * 
     * @return self
     */
    public function addFilter(QueryFilter $filter): self
    {
        $this->filters[] = $filter;
        $this->isProcessed = false;
        return $this;
    }

    private function process(): void
    {
        $this->reset();
        $query = $this->class::selectRaw('status, COUNT(*) AS cnt')->groupBy('status');

        foreach ($this->filters as $filter) {
            $query->filter($filter);
        }

        $result = $query->get()->toArray();
        foreach ($result as $entry) {
            $statusName = array_search($entry['status'], $this->map);
            if ($statusName !== false) {
                $this->cntStatus[$statusName] = $entry['cnt'];
            }
        }

        $this->cntTotal = array_sum(array_values($this->cntStatus));
        $this->isProcessed = true;
    }

    private function createMap()
    {
        $reflection = new ReflectionClass($this->statusClass);
        $this->map = $reflection->getConstants();
    }

    private function reset()
    {
        $this->cntTotal = 0;
        $this->cntStatus = [];
        foreach ($this->map as $statusName => $val) {
            $this->cntStatus[$statusName] = 0;
        }
    }
}
