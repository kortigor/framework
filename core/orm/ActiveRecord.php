<?php

declare(strict_types=1);

namespace core\orm;

use core\interfaces\ModelValidableInterface;
use core\traits\ModelValidateTrait;
use core\helpers\ArrayHelper;
use core\validators\Assert;
use core\exception\InvalidConfigException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Abstract class to build ActiveRecord models.
 *  - Usage information see at: https://laravel.com/docs/8.x/eloquent
 *  - Json relations use package: https://github.com/staudenmeir/eloquent-json-relations
 */
abstract class ActiveRecord extends Model implements ModelValidableInterface
{
    use ModelValidateTrait;
    use QueryFilterableTrait;
    use QuerySortableTrait;
    use HasJsonRelationships;
    use JoinRelationTrait;
    use WhereLikeTrait;

    /**
     * @var bool Validate record before save or no.
     */
    public bool $validateBeforeSave = false;

    /**
     * The attributes that are mass assignable.
     * 
     * Importantly, you should use either $fillable or $guarded - not both.
     * 
     * If you set same attribute fillable and guarded, the attribute will be fillable.
     *
     * @var string[]
     * @see https://laravel.com/docs/8.x/eloquent#mass-assignment
     */
    protected $fillable = [];

    /**
     * The attributes that are no mass assignable.
     * 
     * If you set same attribute fillable and guarded, the attribute will be fillable.
     * 
     * Importantly, you should use either $fillable or $guarded - not both.
     * 
     * If sets to '['*']' it means all attributes is guarded.
     *
     * @var string[]|bool
     * @see https://laravel.com/docs/8.x/eloquent#mass-assignment
     */
    protected $guarded = ['*'];

    /**
     * The attributes that should be hidden for serialization.
     * 
     * @var array
     * @see https://laravel.com/docs/8.x/eloquent-serialization#hiding-attributes-from-json
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     * @see https://laravel.com/docs/8.x/eloquent-serialization#hiding-attributes-from-json
     */
    protected $visible = [];

    /**
     * The attributes that should be cast.
     * This format will be used when the model is serialized to an array or JSON
     * 
     * @var array
     * @see https://laravel.com/docs/8.x/eloquent-mutators#attribute-casting
     */
    protected $casts = [];

    /**
     * The relations to eager load on every query.
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Get the value of the model's primary key.
     * 
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->getKey();
    }

    /**
     * Get table name without instantiate record.
     * 
     * @return string
     */
    public static function getTableName(): string
    {
        return ((new static)->getTable());
    }

    /**
     * Check, without instantiate, that model has specific attribute.
     * 
     * @param string $name Attribute name to check
     * 
     * @return bool True if model has attribute.
     */
    public static function hasAttribute(string $name): bool
    {
        return Capsule::schema()->hasColumn(static::getTableName(), $name);
    }

    /**
     * Returns a single active record model instance by a primary key or an array of column values.
     *
     * The method accepts:
     *
     *  - a scalar value (integer or string): query by a single primary key value and return the
     *    corresponding record (or `null` if not found).
     *  - a non-associative array: query by a list of primary key values and return the
     *    first record (or `null` if not found).
     *  - an associative array of name-value pairs: query by a set of attribute values and return a single record
     *    matching all of them (or `null` if not found). Note that `['id' => 1, 2]` is treated as a non-associative array.
     *    Column names are limited to current records table columns for SQL DBMS, or filtered otherwise to be limited to simple filter conditions.
     *
     *
     * > Note: As this is a short-hand method only, using more complex conditions, like ['!=', 'id', 1] will not work.
     * > If you need to specify more complex conditions, use `find()` in combination with `where()` instead.
     *
     * See the following code for usage examples:
     *
     * ```php
     * // find a single customer whose primary key value is 10
     * $customer = Customer::findOne(10);
     *
     * // the above code is equivalent to:
     * $customer = Customer::find()->where(['id' => 10])->first();
     *
     * // find the customers whose primary key value is 10, 11 or 12.
     * $customers = Customer::findOne([10, 11, 12]);
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['id' => [10, 11, 12]])->first();
     *
     * // find the first customer whose age is 30 and whose status is 1
     * $customer = Customer::findOne(['age' => 30, 'status' => 1]);
     *
     * // the above code is equivalent to:
     * $customer = Customer::find()->where(['age' => 30, 'status' => 1])->first();
     * ```
     *
     * If you need to pass user input to this method, make sure the input value is scalar or in case of
     * array condition, make sure the array structure can not be changed from the outside:
     *
     * ```php
     * // \web\Controller ensures that $id is scalar
     * public function actionView($id)
     * {
     *     $model = Post::findOne($id);
     *     // ...
     * }
     *
     * // explicitly specifying the colum to search, passing a scalar or array here will always result in finding a single record
     * $model = Post::findOne(['id' => $request->get('id')]);
     *
     * // do NOT use the following code! it is possible to inject an array condition to filter by arbitrary column values!
     * $model = Post::findOne($request->get('id'));
     * ```
     *
     * @param mixed $condition primary key value or a set of column values
     * @return ActiveRecord|null record instance matching the condition, or `null` if nothing matches.
     */
    public static function findOne($condition): ?self
    {
        return static::findByCondition($condition)->first();
    }

    /**
     * Returns a list of active record models that match the specified primary key value(s) or a set of column values.
     *
     * The method accepts:
     *
     *  - a scalar value (integer or string): query by a single primary key value and return an array containing the
     *    corresponding record (or an empty array if not found).
     *  - a non-associative array: query by a list of primary key values and return the
     *    corresponding records (or an empty array if none was found).
     *    Note that an empty condition will result in an empty result as it will be interpreted as a search for
     *    primary keys and not an empty `WHERE` condition.
     *  - an associative array of name-value pairs: query by a set of attribute values and return an array of records
     *    matching all of them (or an empty array if none was found). Note that `['id' => 1, 2]` is treated as
     *    a non-associative array.
     *    Column names are limited to current records table columns for SQL DBMS, or filtered otherwise to be limted to simple filter conditions.
     *
     * This method will automatically call the `get()` method and return an array of Model instances.
     *
     * > Note: As this is a short-hand method only, using more complex conditions, like ['!=', 'id', 1] will not work.
     * > If you need to specify more complex conditions, use `find()` in combination with `where()` instead.
     *
     * See the following code for usage examples:
     *
     * ```php
     * // find the customers whose primary key value is 10
     * $customers = Customer::findAll(10);
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['id' => 10])->get();
     *
     * // find the customers whose primary key value is 10, 11 or 12.
     * $customers = Customer::findAll([10, 11, 12]);
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['id' => [10, 11, 12]])->get();
     *
     * // find customers whose age is 30 and whose status is 1
     * $customers = Customer::findAll(['age' => 30, 'status' => 1]);
     *
     * // the above code is equivalent to:
     * $customers = Customer::find()->where(['age' => 30, 'status' => 1])->get();
     * ```
     *
     * If you need to pass user input to this method, make sure the input value is scalar or in case of
     * array condition, make sure the array structure can not be changed from the outside:
     *
     * ```php
     * // \web\Controller ensures that $id is scalar
     * public function actionView($id)
     * {
     *     $model = Post::findOne($id);
     *     // ...
     * }
     *
     * // explicitly specifying the colum to search, passing a scalar or array here will always result in finding a single record
     * $model = Post::findOne(['id' => $request->get('id')]);
     *
     * // do NOT use the following code! it is possible to inject an array condition to filter by arbitrary column values!
     * $model = Post::findOne($equest->get('id'));
     * ```
     *
     * @param mixed $condition primary key value or a set of column values
     * @return Collection|null an array of ActiveRecord instance, or an empty array if nothing matches.
     */
    public static function findAll($condition): ?Collection
    {
        return static::findByCondition($condition)->get();
    }

    /**
     * Finds models by the given condition.
     * @param mixed $condition
     * @return Builder the newly created Builder instance.
     */
    protected static function findByCondition($condition): Builder
    {
        if (ArrayHelper::isAssociative($condition)) {
            return static::where($condition);
        } else {
            return static::whereKey($condition); // query by primary key
        }
    }

    /**
     * Save the model in the database without sync original values.
     *
     * @param array $options
     * @return bool
     * @see syncOriginal()
     * @see save()
     */
    public function saveWithOriginals(array $options = []): bool
    {
        $original = $this->getRawOriginal();
        $result = $this->save($options);
        $this->original = $original;

        return $result;
    }

    /**
     * Save the model and all of its relationships without sync original values.
     *
     * @return bool
     * @see saveWithOriginals()
     * @see syncOriginal()
     * @see push()
     */
    public function pushWithOriginals()
    {
        if (!$this->saveWithOriginals()) {
            return false;
        }

        /* To sync all of the relationships to the database, we will simply spin through
        the relationships and save each model via this "pushWithOriginals" method, which allows
        us to recurse into all of these nested relations for the model instance. */
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection ? $models->all() : [$models];
            foreach (array_filter($models) as $model) {

                /* Some relation models such as:
                '\Illuminate\Database\Eloquent\Relations\Pivot'
                save relation data too, but have no this "pushWithOriginals" method.
                No problem, because no need original values for such internal models, just use standard "push" method. */
                $pushMethod = method_exists($model, 'pushWithOriginals') ? 'pushWithOriginals' : 'push';

                if (!$model->$pushMethod()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * 
     * Added option bool `validateBefore`. If set `true` record will be validated before save operation.
     */
    public function save(array $options = []): bool
    {
        $this->beforeSave();

        $validateBefore = (bool) ArrayHelper::remove($options, 'validateBefore', false);
        $isNeedValidation = $this->validateBeforeSave || $validateBefore;
        if ($isNeedValidation && !$this->validate()) {
            $errors = json_encode(
                $this->getErrors(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            throw new InvalidConfigException(
                sprintf('Unable to save record "%s". Validation errors: %s', get_class($this), $errors)
            );
        }

        $result = parent::save($options);
        if ($result === true) {
            $this->afterSave();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): ?bool
    {
        $this->beforeDelete();
        $result = parent::delete();
        if ($result === true) {
            $this->afterDelete();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->afterFind();
        return $model;
    }

    /**
     * Insert new model or update if exists.
     * Require the columns defining model uniqueness to have a "primary" or "unique" index.
     * 
     * Note: It's just more convenient implementation for MySQL
     * using `INSERT ... ON DUPLICATE KEY UPDATE` Statement.
     * 
     * @param array|null $update Column names to update if model exists.
     * 
     * @return bool True if new model was inserted, otherwise false (if updated).
     * With ON DUPLICATE KEY UPDATE, the affected-rows value per row is:
     *  - 1 if the row is inserted as a new row,
     *  - 2 if an existing row is updated,
     *  - 0 if an existing row is set to its current values.
     * 
     * @see \Illuminate\Database\Query\Builder::upsert();
     * @see https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html
     */
    public function upsertModel(array $update = null): bool
    {
        $data = $this->toArray();
        $update ??= $data;
        if ($this->incrementing === false) {
            ArrayHelper::remove($update, $this->getKeyName());
        }

        $rows = parent::upsert($data, [], $update);
        return $rows === 1;
    }

    /**
     * Add child model to 'hasMany' relation without write into database.
     * 
     * @param ActiveRecord $model Child model to add
     * @param string $hasMany Parent model's 'hasMany' relation method name
     * @param string $belongsTo Child model's 'belongsTo' relation method name
     * 
     * @return self
     */
    public function addToHasMany(ActiveRecord $model, string $hasMany, string $belongsTo): self
    {
        Assert::methodExists($model, $belongsTo);
        Assert::methodExists($this, $hasMany);
        Assert::isInstanceOf($model->$belongsTo(), BelongsTo::class);
        Assert::isInstanceOf($this->$hasMany(), HasMany::class);

        /** @var BelongsTo $belongsToRelation */
        $belongsToRelation = $model->$belongsTo();
        /** @var Collection $hasManyCollection */
        $hasManyCollection = $this->$hasMany;

        $foreignKeyName = $belongsToRelation->getForeignKeyName();
        $ownerKeyName = $belongsToRelation->getOwnerKeyName();
        $model->$foreignKeyName = $this->$ownerKeyName;
        $hasManyCollection->add($model);

        return $this;
    }

    /**
     * This method is called when the ActiveRecord object is created and populated with the query result
     * 
     * Possible to override it
     * 
     * @return void
     */
    public function afterFind(): void
    {
    }

    /**
     * This method is called before save (insert or update) operation
     * 
     * Possible to override it
     * 
     * @return void
     */
    public function beforeSave(): void
    {
    }

    /**
     * This method is called after save (insert or update) operation
     * 
     * Possible to override it
     * 
     * @return void
     */
    public function afterSave(): void
    {
    }

    /**
     * This method is called before delete operation
     * 
     * Possible to override it
     * 
     * @return void
     */
    public function beforeDelete(): void
    {
    }

    /**
     * This method is called after delete operation
     * 
     * Possible to override it
     * 
     * @return void
     */
    public function afterDelete(): void
    {
    }

    /**
     * Search in model's attribute(s)
     * 
     * @param string $searcher Searcher class name
     * 
     * @return Search
     */
    public function search(string $searcher = Search::class): Search
    {
        return new $searcher($this);
    }
}