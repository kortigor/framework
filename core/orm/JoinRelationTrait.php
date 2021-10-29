<?php

declare(strict_types=1);

namespace core\orm;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Model::query()->select('S.*')->withJoin('relationShip1','S');
 * Model::query()->select('P.*')->withJoin(['relationShip1','relationShip2'],'P');
 * Model::query()->select('P.*','S.*')->withJoin(['relationShip1','relationShip2'],['P','S']);
 * Model::query()->from(Model::query()->getModel()->getTable().' as U')->select('U.*','S.*')->withJoin(['relationShip1','relationShip2'],['P','S']);
 * Model::query()->from(Model::query()->getModel()->getTable().' as U')->select('U.*','S.*')->withJoin('relationShip1.relationShip2',['P','S']);
 * Model::query()->select('P.*','S.*')->withSelect('relationShip1.'relationShip2',['alias'=>'farcolumn']);
 * Model::query()->select('P.*','S.*')->withSelect('relationShip1.'relationShip2',['alias'=>DB::Expr('farcolumn_expression')]);
 * Model::query()->select('P.*','S.*')->withSelect('relationShip1.'relationShip2',[DB::Expr('farcolumn_expression')]);
 **/
trait JoinRelationTrait
{
    /**
     * @param Builder $builder
     * @param $relationSegments
     * @param string|string[]|null $rightAlias
     *  - if not provided, it is generated at random and they sell progressively hung
     *  - if supplied as a string it becomes alias of the one on the right and the previous ones will have a numeric suffix N + 1
     *  - if supplied as an array, element zero is used for the furthest relation and so on
     * 
     * @param string $operator
     * @return $this
     * @throws \Exception
     */
    public function scopeWithJoin(Builder $builder, $relationSegments, $rightAlias = null, $decorators = null, $join = 'join', $operator = '=')
    {
        if (is_string($decorators)) {
            if ($join === 'join') {
                $operator = '=';
            } else {
                $operator = $join;
            }
            $join = $decorators;
            $decorators = null;
        } else if ($decorators) {
            $decorators = $this->wrap($decorators);
        }

        // retrieves the name of the table with possible alias from the from or from the name of the corresponding table in the model
        $aliasSegments = preg_split('/\s+/i', $previousTableAlias = $builder->getQuery()->from ?: $builder->getModel()->getTable());

        // the third would contain the alias
        if (is_array($aliasSegments) && isset($aliasSegments[2])) {
            $previousTableAlias = $aliasSegments[2];
        }

        $this->getJoinRelationSubQuery($this, $builder, $relationSegments, $previousTableAlias, $rightAlias, false, $decorators, $join, $operator);

        return $builder;
    }

    public function scopeWithJoinLeft(Builder $builder, $relationSegments, $rightAlias = null, $decorators = null, $operator = '=')
    {
        return $this->scopeWithJoin($builder, $relationSegments, $rightAlias, $decorators, 'leftJoin', $operator);
    }

    public function scopeWithSelect(Builder $builder, $relationSegments, $columns, $rightAlias = null, $decorators = null, $join = 'join', $operator = '=')
    {
        if (is_string($decorators)) {
            if ($join === 'join') {
                $operator = '=';
            } else {
                $operator = $join;
            }
            $join = $decorators;
            $decorators = null;
        } else if ($decorators) {
            $decorators = $this->wrap($decorators);
        }

        // retrieves the name of the table with possible alias from the from or from the name of the corresponding table in the model
        $aliasSegments = preg_split('/\s+/i', $previousTableAlias = $builder->getQuery()->from ?: $builder->getModel()->getTable());
        // the third would contain the alias
        if (is_array($aliasSegments) && isset($aliasSegments[2])) {
            $previousTableAlias = $aliasSegments[2];
        }

        $this->getJoinRelationSubQuery($this, $subQuery = DB::query(), $relationSegments, $previousTableAlias, $rightAlias, true, $decorators, $join, $operator);

        foreach ($columns as $alias => $column) {
            $subQuery->addSelect($column);
            $builder->selectSub($subQuery, is_string($alias) ? $alias : $this->wrapColumnDefinition($column));
        }
        return $builder;
    }

    protected function wrapColumnDefinition($columnDefinition)
    {
        return urldecode(
            $this->getQuery()->getGrammar()->wrap(
                preg_replace_callback('/[^a-zA-Z]+/', function ($value) {
                    $value = $value[0];
                    $out = '';
                    for ($i = 0; isset($value[$i]); $i++) {
                        $c = $value[$i];
                        if (!ctype_alnum($c)) $c = '%' . sprintf('%02X', ord($c));
                        $out .= $c;
                    }
                    return $out;
                }, $columnDefinition)
            )
        );
    }

    /**
     * Starting from a model, it creates multiple joins by exploiting relationships
     *
     * @param Model $model
     * @param $relationSegments
     * @param null $builder
     * @param null $previousTableAlias
     * @return null
     * @throws \Exception
     */
    protected function getJoinRelationSubQuery(
        Model $model,
        $builder,
        $relationSegments,
        $previousTableAlias = null,
        $rightAlias = null,
        $sub = false,
        $decorators = null,
        $join = 'join',
        $operator = '='
    ) {

        $currentModel = $model;
        $relatedModel = null;
        $relatedTableAlias = null;
        $tableAliases = [];
        $relatedTableAndAlias = null;
        $relationSegments = $this->wrap($relationSegments);
        if (count($relationSegments) == 1 && Str::contains($relationSegments[0], '.')) {
            $relationSegments = preg_split('/\./', $relationSegments[0]);
        }
        if (($relationIndex = count($relationSegments)) == 0) {
            throw new \Exception('Relation path cannot be empty');
        }
        /**
         * The prefix for joined tables in JOIN is generated randomly if not provided
         */
        // $randomPrefix = Str::randomStringAlpha(3);
        $randomPrefix = \core\helpers\StringHelper::randomStringAlpha(3);
        /**
         * For each segment I add a join
         */
        foreach ($relationSegments as $segment) {
            if (!method_exists($currentModel, $segment)) {
                throw new \BadMethodCallException("Relationship $segment does not exist, cannot join.");
            }
            $decorator = $this->getDecorator($decorators, $relationIndex);
            $relation = $currentModel->$segment();
            $relatedModel = $relation->getRelated();
            $relatedTableAlias = $this->makeTableAlias($randomPrefix, $rightAlias, $relationIndex);
            if (!is_null($relatedTableAlias)) {
                $tableAlias = ' AS ' . $relatedTableAlias;
                $relatedTableAndAlias = $relatedModel->getTable() . $tableAlias;
            } else {
                $relatedTableAndAlias = $relatedTableAlias = $relatedModel->getTable();
            }
            $tableAliases[] = $relatedTableAlias;
            /**
             * In the BelongsTo we define:
             * - CHILD TABLE (SX): the one on the side with cardinality N
             * - FOREIGN KEY: the foreign key column on the CHILD table or the side with cardinality (N)
             * - PARENT TABLE (DX): the one on the side with cardinality 1
             * - OWNER KEY: the key column on the PARENT table or the side with cardinality (1)
             */
            if ($relation instanceof BelongsTo) {
                if ($sub) {
                    if (!$previousTableAlias) {
                        throw new \RuntimeException('$previousTableAlias is required for sub');
                    }
                    $sub = false;
                    $builder
                        ->from($relatedTableAndAlias)
                        ->whereColumn(
                            $previousTableAlias . '.' . $relation->getForeignKey(),
                            $operator,
                            $relatedTableAlias . '.' . $relation->getOwnerKey()
                        );
                } else {
                    $builder->$join(
                        $relatedTableAndAlias,
                        function (JoinClause $joinClause) use ($decorator, $previousTableAlias, $relation, $operator, $relatedTableAlias) {
                            $joinClause->on(
                                $previousTableAlias ? $previousTableAlias . '.' . $relation->getForeignKey() : $relation->getQualifiedForeignKey(),
                                $operator,
                                $relatedTableAlias . '.' . $relation->getOwnerKey()
                            );
                            if ($decorator instanceof \Closure) {
                                $decorator($joinClause);
                            }
                        }
                    );
                }
            }
            /**
             * In HasOneOrMany we define:
             * - PARENT TABLE (SX): the one on the side with cardinality 1
             * - CHILD TABLE (DX): the one on the side with cardinality N
             */
            elseif ($relation instanceof HasOneOrMany) {
                if ($sub) {
                    if (!$previousTableAlias) {
                        throw new \RuntimeException('$previousTableAlias is required for sub');
                    }
                    $sub = false;
                    $builder
                        ->from($relatedTableAndAlias)
                        ->whereColumn(
                            $previousTableAlias . '.' . $relation->getParent()->getKeyName(),
                            $operator,
                            $relatedTableAlias . '.' . $relation->getForeignKeyName()
                        );
                } else {
                    $builder
                        ->$join(
                            $relatedTableAndAlias,
                            function (JoinClause $joinClause) use ($decorator, $previousTableAlias, $relation, $operator, $relatedTableAlias) {
                                $joinClause->on(
                                    $previousTableAlias ? $previousTableAlias . '.' . $relation->getParent()->getKeyName() : $relation->getQualifiedParentKeyName(),
                                    $operator,
                                    $relatedTableAlias . '.' . $relation->getForeignKeyName()
                                );
                                if ($decorator instanceof  \Closure) {
                                    $decorator($joinClause);
                                }
                            }
                        );
                }
            } else {
                throw new \InvalidArgumentException(
                    sprintf("Relation $segment of type %s is not supported", get_class($relation))
                );
            }
            /**
             * Advance pointers
             */
            $currentModel = $relatedModel;
            $previousTableAlias = $relatedTableAlias;
            $relationIndex--;
        }

        return $tableAliases;
    }

    /**
     * Creates an alias with a numeric prefix or retrieves it from an array
     *
     * If index is not present it takes next element
     *
     * @param $string $prefix
     * @param string|array $alias
     * @return array|mixed|null|string
     */
    public function makeTableAlias($prefix, $alias, $index)
    {
        $index -= 1;
        if (is_array($alias)) {
            if (isset($alias[$index]))
                return $alias[$index];
            if ($index > 0)
                return $this->last($alias) . '_' . ($index);
            return $this->last($alias);
        } else if ($index == 0) {
            if (is_null($alias))
                return null;
            return $alias;
        }
        return $prefix . '_' . ($index);
    }

    public function getDecorator($decorators, $index)
    {
        $index -= 1;
        if (!is_array($decorators)) {
            return null;
        }
        if (isset($decorators[$index]))
            return $decorators[$index];
        return null;
    }

    // Functions behind placed additionally, because we use Eloquent without Laravel

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param mixed $value
     * @return array
     */
    private function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array  $array
     * @param callable|null  $callback
     * @param mixed $default
     * @return mixed
     */
    private function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $this->value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return $this->value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    private function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? $this->value($default) : end($array);
        }

        return $this->first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}