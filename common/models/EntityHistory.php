<?php

namespace common\models;

use Sys;
use core\orm\ActiveRecord;
use core\helpers\ArrayHelper;
use common\events\EntityUpdateEventInterface;
use common\events\EntityRelationUpdateEventInterface;

/**
 * Entity history model.
 * 
 * @property int $id
 * @property string $event_class History event class name
 * @property string $model_id History trackable model id
 * @property string $model_class History trackable model class name
 * @property array $data_changed Model's changed attributes (JSON)
 * @property array $copy_before Model's attributes before change (JSON)
 * @property string $user_id User who made changes ID
 * @property string $user_ip User who made changes IP address
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 * @property-read User $user User object, who made entity's changes.
 */
class EntityHistory extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    protected $table = 'history';

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'model_id',
        'event_class',
        'model_class',
        'data_changed',
        'copy_before',
        'user_id',
        'user_ip',
    ];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'data_changed' => 'array',
        'copy_before' => 'array',
    ];


    public static function buildFromEntityEvent(EntityUpdateEventInterface $event): self
    {
        $entity = $event->getModel();
        $changes = $entity->getChanges();
        ArrayHelper::remove($changes, 'updated_at');
        $new = new self([
            'model_id' => $entity->getPrimaryKey(),
            'model_class' => get_class($entity),
            'event_class' => get_class($event),
            'copy_before' => $entity->getRawOriginal(),
            'data_changed' => $changes,
            'user_id' => Sys::$app->user->identity?->getId() ?? '',
            'user_ip' => Sys::$app->remoteIp
        ]);

        return $new;
    }

    public static function buildFromRelationEvent(EntityRelationUpdateEventInterface $event): self
    {
        $entity = $event->getModel();
        $changes = $event->getChanges();
        $before = $event->getCollectionBefore()->pluck('name')->toArray();
        $new = new self([
            'model_id' => $entity->getPrimaryKey(),
            'model_class' => get_class($entity),
            'event_class' => get_class($event),
            'copy_before' => $before,
            'data_changed' => $changes,
            'user_id' => Sys::$app->user->identity?->getId() ?? '',
            'user_ip' => Sys::$app->remoteIp
        ]);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['model_class', 'model_id', 'user_id', 'user_ip'], 'required'],
            [['model_id', 'user_id'], 'uuid'],
            [['data_changed', 'copy_before'], 'json'],
            ['model_class', 'classExists'],
            ['user_ip', 'ip'],
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}