<?php

declare(strict_types=1);

namespace core\orm;

use Closure;
use InvalidArgumentException;
use core\web\ServerRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

abstract class QueryFilterSort extends QueryFilterConfigurable implements QuerySortableleInterface
{
    /**
     * @var array the order that should be used when the current request does not specify any order.
     * The array keys are attribute names and the array values are the corresponding sort directions. For example,
     *
     * ```php
     * [
     *     'name' => SORT_ASC,
     *     'created_at' => SORT_DESC,
     * ]
     * ```
     */
    public array $default = [];

    /**
     * @var array list of fields that are allowed to be sorted. Its syntax can be
     * described using the following example:
     *
     * ```php
     * [
     *     'age',
     *     'name' => [
     *         'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
     *         'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
     *     ],
     * ]
     * ```
     *
     * In the above, two fields are declared: `age` and `name`. The `age` attribute is
     * a simple attribute which is equivalent to the following:
     *
     * ```php
     * 'age' => [
     *     'asc' => ['age' => SORT_ASC],
     *     'desc' => ['age' => SORT_DESC],
     * ]
     * ```
     *
     * Particular sort direction can be also specified as Closure, like following:
     *
     * ```php
     * 'name' => [
     *     'asc' => fn($query) => $query->orderBy('last_name', 'asc'),
     *     'desc' => fn($query) => $query->orderBy('last_name', 'desc'),
     * ]
     * ```
     *
     * The `name` attribute is a composite attribute:
     *
     * - The `name` key represents the attribute name which will appear in the URLs leading
     *   to sort actions.
     * - The `asc` and `desc` elements specify how to sort by the attribute in ascending
     *   and descending orders, respectively. Their values represent the actual columns and
     *   the directions by which the data should be sorted by.
     *
     * Note that if the QueryFilterSort object is already created, you can only use the full format
     * to configure every attribute. Each attribute must include these elements: `asc` and `desc`.
     */
    public array $fields = [];

    /**
     * Constructor.
     * 
     * @param ServerRequest $request
     * @param array $fields List of model fields to sort by.
     * @param array $parameters Request parameters names to handle.
     */
    public function __construct(ServerRequest $request, array $fields = [], array $parameters = ['sort'])
    {
        parent::__construct($request, $fields, $parameters);
        $this->normalizeFields();
    }

    /**
     * Default sorting filter
     * 
     * @param string|array $value
     * 
     * @return void
     */
    public function defaultFilter()
    {
        $this->build($this->default);
    }

    /**
     * @return void
     */
    protected function normalizeFields(): void
    {
        $fields = [];
        foreach ($this->fields as $name => $record) {
            if (!is_array($record)) {
                $fields[$record] = [
                    'asc' => [$record => SORT_ASC],
                    'desc' => [$record => SORT_DESC],
                ];
            } elseif (!isset($record['asc'], $record['desc'])) {
                $fields[$name] = array_merge([
                    'asc' => [$name => SORT_ASC],
                    'desc' => [$name => SORT_DESC],
                ], $record);
            } else {
                $fields[$name] = $record;
            }
        }
        $this->fields = $fields;
    }

    /**
     * {@inheritDoc}
     * 
     * Build sorting query from request parameter like 'path?sort=title,asc'
     * 
     * @param string|array $value
     * @return void
     */
    public function build(string|array $value)
    {
        foreach ($this->getMap($value) as $field => $direction) {
            if ($field instanceof Closure) {
                $closure = $field;
            } else {
                $closure = str_contains($field, '.')
                    ? $this->getWithRelation($field, $direction)
                    : $this->getWithoutRelation($field, $direction);
            }

            $closure($this->builder);
        }
    }

    /**
     * Convert query from request parameter like: '/path?sort[]=title|asc&sort[]=order|desc'
     * into iterator of sorting definitions like: `['title' => 'asc'], ['order => desc']...`
     * 
     * @param string|array $value
     * 
     * @return iterable
     */
    protected function getMap(string|array $value): iterable
    {
        foreach ((array) $value as $sort) {
            if (str_contains($sort, $this->delimiter)) {
                list($field, $direction) = $this->paramToArray($sort);
            } else {
                $field = $sort;
                $direction = 'asc';
            }

            if (!$this->isSortable($field)) {
                continue;
            }

            $direction = $this->isDirectionValid($direction) ? $direction : 'asc';
            $definition = $this->fields[$field][$direction];
            foreach ($definition as $attribute => $order) {
                $attributeOrder = $order === SORT_DESC ? 'desc' : 'asc';
                yield $attribute => $attributeOrder;
            }
        }
    }

    /**
     * Add sorting with model's field, no relation
     * 
     * @param string $field
     * @param string $direction
     * 
     * @return Closure
     * @throws InvalidArgumentException From orderBy(), if direction is invalid
     */
    protected function getWithoutRelation(string $field, string $direction): Closure
    {
        return fn ($query) => $query->orderBy($field, $direction);
    }

    /**
     * @param string $field
     * @param string $direction
     * 
     * @return Closure
     * @throws InvalidArgumentException From orderBy(), if direction is invalid
     */
    protected function getWithRelation(string $field, string $direction): Closure
    {
        $buffer = explode('.', $field);
        $attributeField = array_pop($buffer);
        $attributeRelation = array_shift($buffer);
        $model = $this->builder->getModel();

        /** @var Relation $relation */
        $relation = $model->$attributeRelation();
        if (is_a($relation, HasOneOrMany::class) || is_a($relation, BelongsTo::class)) {
            return $this->getOneToMany($model, $relation, $attributeField, $direction);
        }

        if (is_a($relation, BelongsToMany::class)) {
            return $this->getManyToMany($model, $relation, $attributeField, $direction);
        }

        if (is_a($relation, HasManyThrough::class)) {
            return $this->getManyToManyThrough($model, $relation, $attributeField, $direction);
        }

        throw new InvalidArgumentException("Unsupported sorting relation " . get_class($relation));
    }

    /**
     * Add sorting query for one-to-many or many-to-one relations (using two tables)
     * 
     * @param ActiveRecord $model
     * @param HasOneOrMany|BelongsTo $relation
     * @param string $field
     * @param string $direction
     * 
     * @return Closure
     * @throws InvalidArgumentException From orderBy(), if direction is invalid
     */
    protected function getOneToMany(ActiveRecord $model, HasOneOrMany|BelongsTo $relation, string $field, string $direction): Closure
    {
        $table = $model->getTable();
        $tableRelated = $relation->getRelated()->getTable();

        return fn ($query) => $query->join(
            $tableRelated,
            fn ($join) => $join
                ->on($relation->getQualifiedForeignKeyName(), '=', $relation->getQualifiedParentKeyName())
                ->when(
                    Translatable::is($model),
                    fn ($sub) => $sub->where("{$tableRelated}.language", '=', $model->getDefaultLanguage())
                )
        )->addSelect("{$table}.*")->orderBy($field, $direction);
    }

    /**
     * Add sorting query for many-to-many relations
     * 
     * @param ActiveRecord $model
     * @param HasOneOrMany $relation
     * @param string $field
     * @param string $direction
     * 
     * @return Closure
     * @throws InvalidArgumentException From orderBy(), if direction is invalid
     */
    protected function getManyToMany(ActiveRecord $model, BelongsToMany $relation, string $field, string $direction): Closure
    {
        throw new InvalidArgumentException(__METHOD__ . " to be developed");
    }

    /**
     * Add sorting query for relations using through model
     * 
     * @param ActiveRecord $model
     * @param HasOneOrMany $relation
     * @param string $field
     * @param string $direction
     * 
     * @return Closure
     * @throws InvalidArgumentException From orderBy(), if direction is invalid
     */
    protected function getManyToManyThrough(ActiveRecord $model, HasManyThrough $relation, string $field, string $direction): Closure
    {
        throw new InvalidArgumentException(__METHOD__ . " to be developed");
    }

    /**
     * Whether sorting direction is correct.
     * 
     * @param string $direction
     * 
     * @return bool
     */
    protected function isDirectionValid(string $direction): bool
    {
        return in_array(strtolower($direction), ['asc', 'desc']);
    }

    /**
     * Whether field is sortable according 
     * 
     * @param string $field Field name
     * 
     * @return bool
     * @see $fields
     */
    protected function isSortable(string $field): bool
    {
        return isset($this->fields[$field]);
    }
}