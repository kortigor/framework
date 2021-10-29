<?php

declare(strict_types=1);

namespace core\web;

use core\base\Model;
use core\traits\GetSetByPropsTrait;
use core\helpers\ArrayHelper;
use core\helpers\Html;

/**
 * UploadedFile represents the information for an uploaded file.
 *
 * You can call `getInstance()` to retrieve the instance of an uploaded file,
 * and then use `saveAs()` to save it on the server.
 * You may also query other information about the file, including
 * `name`, `tempName`, `type`, `size` and `error`.
 *
 * @property string $baseName Original file base name. This property is read-only.
 * @property string $extension File extension. This property is read-only.
 * @property bool $hasError Whether there is an error with the uploaded file. Check [[error]] for detailed
 * error code information. This property is read-only.
 * 
 * @deprecated Use PSR-7 compatible `\core\http\UploadedFile` class instead
 */
class UploadedFile
{
    use GetSetByPropsTrait;

    /**
     * @var string the original name of the file being uploaded
     */
    public $name;
    /**
     * @var string the path of the uploaded file on the server.
     * Note, this is a temporary file which will be automatically deleted by PHP
     * after the current request is processed.
     */
    public $tempName;
    /**
     * @var string the MIME-type of the uploaded file (such as "image/gif").
     * Since this MIME type is not checked on the server-side, do not take this value for granted.
     * Instead, use \core\helpers\FileHelper::getMimeType() to determine the exact MIME type.
     */
    public $type;
    /**
     * @var int the actual size of the uploaded file in bytes
     */
    public $size;
    /**
     * @var int an error code describing the status of this file uploading.
     * @see https://secure.php.net/manual/en/features.file-upload.errors.php
     */
    public $error;

    /**
     * @var resource a temporary uploaded stream resource used within PUT and PATCH request.
     */
    private $_tempResource;
    private static $_files;


    /**
     * UploadedFile constructor.
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(array $config = [])
    {
        $this->_tempResource = ArrayHelper::remove($config, 'tempResource');
        foreach ($config as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    /**
     * String output.
     * This is PHP magic method that returns string representation of an object.
     * The implementation here returns the uploaded file's name.
     * @return string the string representation of the object
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Returns an uploaded file for the given model attribute.
     * The file should be uploaded using `\core\activeform\ActiveField::fileInput()`.
     * @param \core\base\Model $model the data model
     * @param string $attribute the attribute name. The attribute name may contain array indexes.
     * For example, '[1]file' for tabular file uploading; and 'file[1]' for an element in a file array.
     * @return null|UploadedFile the instance of the uploaded file.
     * Null is returned if no file is uploaded for the specified model attribute.
     * @see getInstanceByName()
     */
    public static function getInstance(Model $model, string $attribute): ?self
    {
        $name = Html::getInputName($model, $attribute);
        return static::getInstanceByName($name);
    }

    /**
     * Returns all uploaded files for the given model attribute.
     * @param \core\base\Model $model the data model
     * @param string $attribute the attribute name. The attribute name may contain array indexes
     * for tabular file uploading, e.g. '[1]file'.
     * @return UploadedFile[] array of UploadedFile objects.
     * Empty array is returned if no available file was found for the given attribute.
     */
    public static function getInstances(Model $model, string $attribute): array
    {
        $name = Html::getInputName($model, $attribute);
        return static::getInstancesByName($name);
    }

    /**
     * Returns an uploaded file according to the given file input name.
     * The name can be a plain string or a string like an array element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
     * @param string $name the name of the file input field.
     * @return null|UploadedFile the instance of the uploaded file.
     * Null is returned if no file is uploaded for the specified name.
     */
    public static function getInstanceByName(string $name): ?self
    {
        $files = static::loadFiles();
        return isset($files[$name]) ? new static($files[$name]) : null;
    }

    /**
     * Returns an array of uploaded files corresponding to the specified file input name.
     * This is mainly used when multiple files were uploaded and saved as 'files[0]', 'files[1]',
     * 'files[n]'..., and you can retrieve them all by passing 'files' as the name.
     * @param string $name the name of the array of files
     * @return UploadedFile[] the array of UploadedFile objects. Empty array is returned
     * if no adequate upload was found. Please note that this array will contain
     * all files from all sub-arrays regardless how deeply nested they are.
     */
    public static function getInstancesByName(string $name): array
    {
        $files = static::loadFiles();
        if (isset($files[$name])) {
            return [new static($files[$name])];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = new static($file);
            }
        }

        return $results;
    }

    /**
     * Cleans up the loaded UploadedFile instances.
     * This method is mainly used by test scripts to set up a fixture.
     */
    public static function reset(): void
    {
        static::$_files = null;
    }

    /**
     * Saves the uploaded file.
     * If the target file `$file` already exists, it will be overwritten.
     * @param string $file the file path used to save the uploaded file.
     * @param bool $deleteTempFile whether to delete the temporary file after saving.
     * If true, you will not be able to save the uploaded file again in the current request.
     * @return bool true whether the file is saved successfully
     * @see error
     */
    public function saveAs(string $file, bool $deleteTempFile = true): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }
        if ($this->copyTempFile($file) === null) {
            return false;
        }
        return !$deleteTempFile || $this->deleteTempFile();
    }

    /**
     * Copy temporary file into file specified
     *
     * @param string $targetFile path of the file to copy to
     * @return null|int the total count of bytes copied, or null on failure
     */
    protected function copyTempFile(string $targetFile): ?int
    {
        if (!is_resource($this->_tempResource)) {
            return $this->isUploadedFile($this->tempName) && copy($this->tempName, $targetFile);
        }
        $target = fopen($targetFile, 'wb');
        if ($target === false) {
            return null;
        }

        $result = stream_copy_to_stream($this->_tempResource, $target);
        @fclose($target);

        return $result;
    }

    /**
     * Delete temporary file
     *
     * @return bool if file was deleted
     */
    protected function deleteTempFile(): bool
    {
        if (is_resource($this->_tempResource)) {
            return @fclose($this->_tempResource);
        }
        return $this->isUploadedFile($this->tempName) && @unlink($this->tempName);
    }

    /**
     * Check if file is uploaded file
     *
     * @param string $file path to the file to check
     * @return bool
     */
    protected function isUploadedFile(string $file): bool
    {
        return is_uploaded_file($file);
    }

    /**
     * @return string original file base name
     */
    public function getBaseNameAttribute(): ?string
    {
        $pathInfo = pathinfo('_' . $this->name, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo));
    }

    /**
     * @return string file extension
     */
    public function getExtensionAttribute(): ?string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * @return bool whether there is an error with the uploaded file.
     * Check [[error]] for detailed error code information.
     */
    public function getHasErrorAttribute(): bool
    {
        return $this->error != UPLOAD_ERR_OK;
    }

    /**
     * Creates UploadedFile instances from $_FILE.
     * @return array the UploadedFile instances
     */
    private static function loadFiles(): array
    {
        if (static::$_files === null) {
            static::$_files = [];
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $class => $info) {
                    $resource = $info['tmp_resource'] ?? [];
                    static::loadFilesRecursive(
                        $class,
                        $info['name'],
                        $info['tmp_name'],
                        $info['type'],
                        $info['size'],
                        $info['error'],
                        $resource
                    );
                }
            }
        }

        return static::$_files;
    }

    /**
     * Creates UploadedFile instances from $_FILE recursively.
     * @param string $key key for identifying uploaded file: class name and sub-array indexes
     * @param mixed $names file names provided by PHP
     * @param mixed $tempNames temporary file names provided by PHP
     * @param mixed $types file types provided by PHP
     * @param mixed $sizes file sizes provided by PHP
     * @param mixed $errors uploading issues provided by PHP
     */
    private static function loadFilesRecursive(string $key, $names, $tempNames, $types, $sizes, $errors, $tempResources): void
    {
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                $resource = $tempResources[$i] ?? [];
                static::loadFilesRecursive(
                    $key . '[' . $i . ']',
                    $name,
                    $tempNames[$i],
                    $types[$i],
                    $sizes[$i],
                    $errors[$i],
                    $resource
                );
            }
        } elseif ((int) $errors !== UPLOAD_ERR_NO_FILE) {
            static::$_files[$key] = [
                'name' => $names,
                'tempName' => $tempNames,
                'tempResource' => $tempResources,
                'type' => $types,
                'size' => $sizes,
                'error' => $errors,
            ];
        }
    }
}