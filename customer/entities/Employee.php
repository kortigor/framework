<?php

declare(strict_types=1);

namespace customer\entities;

use core\base\JsonObject;
use core\entities\Id;
use core\helpers\FileHelper;
use common\models\User;

/**
 * Employee implementation
 * 
 * @property-read string $imageHtml User image href
 * @property-read string $imagePhpPath User image file system path
 * @property-read string $imageDirPhp User images directory to save
 * @property-read Status $status Employee status
 * @property-read EmployeeRole $role Employee role
 * @property JsonObject $options Employee options
 */
final class Employee extends User implements AggregateRootInterface
{
    use AggregateTrait;
    use AggregateTraitStatusEmployee;

    public static string $imageDirectory = 'user_avatars';

    private string $oldImage;

    private JsonObject $_options;

    protected $casts = [
        'birthdate' => 'date:d.m.Y',
        'options' => casts\SerializeImmutable::class,
        'status' => casts\SerializeImmutable::class,
        'role' => casts\SerializeImmutable::class,
    ];

    public static function buildEmpty(): self
    {
        $employee = new self();
        $employee->id = Id::next()->getId();
        $employee->options = 'null';
        $employee->birthdate = '1980-01-01';
        return $employee;
    }

    /**
     * Return user image web path
     * 
     * @return null|string user html image path if exists, null if no.
     */
    public function getImageHtmlAttribute(): ?string
    {
        return $this->userimage ? DATA_ROOT_HTML . static::$imageDirectory . '/' . $this->userimage : null;
    }

    /**
     * Return user image file system path
     * 
     * @return null|string user image file path if exists, null if no.
     */
    public function getImagePhpPathAttribute(): ?string
    {
        return $this->userimage ? $this->imageDirPhp . DS . $this->userimage : null;
    }

    /**
     * Return directory where user image stored in.
     * 
     * @return string
     */
    public function getImageDirPhpAttribute(): string
    {
        return DATA_ROOT_PHP . DS . static::$imageDirectory;
    }

    /**
     * Options get mutator
     * 
     * @return JsonObject
     */
    public function getOptionsAttribute($value): JsonObject
    {
        if (!isset($this->_options)) {
            $this->_options = new JsonObject($value);
        }
        return $this->_options;
    }

    /**
     * Options set mutator
     * 
     * @param mixed $value
     * 
     * @return string
     */
    public function setOptionsAttribute($value): string
    {
        if (!is_array($value) && !is_object($value)) {
            $value = null;
        } elseif (empty((array) $value)) {
            $value = null;
        }

        return $this->attributes['options'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Role get mutator
     * 
     * @return EmployeeRole
     */
    public function getRoleAttribute($value): EmployeeRole
    {
        return new EmployeeRole($value);
    }

    /**
     * Change employee image
     * 
     * @param string $image
     * 
     * @return void
     */
    public function changeImage(string $image): void
    {
        $this->userimage = $image;
    }

    /**
     * Remove old image. It works only after object was saved.
     * 
     * @return void
     */
    public function removeOldImage(): void
    {
        if (!$this->wasChanged('userimage')) {
            return;
        }
        $path = FileHelper::getFilePath($this->imageDirPhp, $this->oldImage);
        FileHelper::unlinkFile($path);
    }

    public function removeImage(): void
    {
        if (!$this->userimage) {
            return;
        }
        FileHelper::unlinkFile($this->imagePhpPath);
        $this->userimage = '';
    }

    public function afterFind(): void
    {
        $this->oldImage = $this->getAttribute('userimage') ?? '';
    }

    public function beforeSave(): void
    {
        $this->options = $this->options->toArray();
        parent::beforeSave();
    }
}
