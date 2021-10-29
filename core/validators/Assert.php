<?php

declare(strict_types=1);

namespace core\validators;

use DateTime;
use InvalidArgumentException;
use BadMethodCallException;
use core\orm\ActiveRecord;
use core\http\UploadedFile;

/**
 * {@inheritdoc}
 */
class Assert extends \Webmozart\Assert\Assert
{
    /**
     * Assert value is not empty.
     * 
     * Useful to check required form fields.
     * 
     * @param mixed $value
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function required($value, $message = ''): void
    {
        try {
            if (is_string($value) || is_numeric($value)) {
                static::stringNotEmpty((string) $value);
            } elseif (is_countable($value)) {
                static::minCount($value, 1);
            } elseif ($value instanceof UploadedFile) {
                /** @var UploadedFile $value */
                static::same($value->getError(), UPLOAD_ERR_OK);
                static::notEmpty($value->getClientFilename());
            } else {
                throw new InvalidArgumentException;
            }
        } catch (InvalidArgumentException) {
            static::reportInvalidArgument(
                sprintf(
                    $message ?: 'Value is required. Got: %s',
                    static::valueToString($value)
                )
            );
        }
    }

    /**
     * Does no strict comparison, so Assert::inArray(3, ['3']) pass the assertion.
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function inArray($value, array $values, $message = ''): void
    {
        if (!in_array($value, $values, false)) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected value in array of: %2$s. Got: %s',
                static::valueToString($value),
                implode(', ', array_map(array('static', 'valueToString'), $values))
            ));
        }
    }

    /**
     * Does strict comparison, so Assert::inArray(3, ['3']) does not pass the assertion.
     *
     * @param mixed  $value
     * @param array  $values
     * @param string $message
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function inArrayStrict($value, array $values, $message = ''): void
    {
        parent::inArray($value, $values, $message);
    }

    /**
     * Assert if a value is considered as boolean.
     * 
     * It means that "boolean" value can be
     * - boolean: true|false;
     * - integer: 0|1
     * - string: '0'|'1'|'true'|'false'|'yes'|'no'|'on'|'off'
     * 
     * Useful to check checkboxes form fields.
     * 
     * @param mixed $value
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function booleanVal($value, $message = ''): void
    {
        try {
            if (is_bool($value)) {
                return;
            } elseif (is_int($value)) {
                static::range($value, 0, 1);
            } elseif (is_string($value)) {
                static::regex($value, '/^([01]{1}|true|false|yes|no|on|off)$/');
            } else {
                throw new InvalidArgumentException;
            }
        } catch (InvalidArgumentException) {
            static::reportInvalidArgument(
                sprintf(
                    $message ?: 'Expected string value is boolean. Got: %s',
                    static::valueToString($value)
                )
            );
        }
    }

    /**
     * Assert ActiveRecord attribute is unique the corresponding database table
     * 
     * @param mixed $value
     * @param string $attribute Record's attribute name
     * @param ActiveRecord $model Record to be check
     * @param array $filter Additional WHERE condition applied to DB query to be filtered out
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function uniqueAttribute($value, string $attribute, ActiveRecord $model, array $filter = [], $message = ''): void
    {
        $condition = [
            [$attribute, '=', $value], // attribute value condition
            [$model->getKeyName(), '<>', $model->getKey()] // and filter out record which we check
        ];

        if ($filter) {
            $condition[] = $filter;
        }

        if ($model::where($condition)->first() !== null) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected attribute value should be unique. Got: %s',
                static::valueToString($value)
            ));
        }
    }

    /**
     * Assert uploaded file size not greather than specified size
     * 
     * @param mixed $value
     * @param int $size maximum allowed file size in bytes
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function uploadedSize($value, int $size, $message = ''): void
    {
        if (!$value instanceof UploadedFile || $value->getError() !== UPLOAD_ERR_OK) {
            return;
        }

        if ($value->getSize() > $size) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected uploaded file size not greater than %u. Got: %s',
                $size,
                static::valueToString($value->getSize())
            ));
        }
    }

    /**
     * Assert uploaded file extension match to allowed types
     * 
     * @param mixed $value
     * @param string $extension allowed extension(s): comma separated list, i.e. 'jpg,png,gif'
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function uploadedExtension($value, string $extension, $message = ''): void
    {
        if (!$value instanceof UploadedFile || $value->getError() !== UPLOAD_ERR_OK) {
            return;
        }

        $extList = array_map('trim', explode(',', $extension));
        if (!in_array($value->getExtension(), $extList)) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected uploaded file extension match %s. Got: %s',
                $extension,
                static::valueToString($value->getExtension())
            ));
        }
    }

    /**
     * Assert uploaded file MIME type match to allowed types
     * 
     * @param mixed $value
     * @param string $type allowed MIME type(s) (comma separated) list, i.e. 'image/png,image/jpeg'
     * @param string $message
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public static function uploadedType($value, string $type, $message = ''): void
    {
        if (!$value instanceof UploadedFile || $value->getError() !== UPLOAD_ERR_OK) {
            return;
        }

        $typeList = array_map('trim', explode(',', $type));
        if (!in_array($value->getClientMediaType(), $typeList)) {
            static::reportInvalidArgument(sprintf(
                $message ?: 'Expected uploaded file MIME type match %s. Got: %s',
                $type,
                static::valueToString($value->getClientMediaType())
            ));
        }
    }

    /**
     * Assert valid date
     *
     * @param mixed $value
     * @param string $format date format accepted by `date()` function
     * @param string $message
     * 
     * @return void
     * @see date()
     */
    public static function date($value, string $format = 'd.m.Y', $message = ''): void
    {
        $date = DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            static::reportInvalidArgument(sprintf($message ?: 'Expected valid date. Got: %s', $value));
        }
    }

    /**
     * Assert valid JSON
     * 
     * @param mixed $value
     * @param string $message
     * 
     * @return void
     */
    public static function json($value, $message = ''): void
    {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            static::reportInvalidArgument(sprintf($message ?: 'Expected JSON encoded string. Got: %s', $value));
        }
    }

    /**
     * Assert valid absolute url.
     * 
     * @param string $value
     * 
     * @return void
     */
    public static function url($value, $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            static::reportInvalidArgument(sprintf($message ?: 'Expected valid url. Got: %s', $value));
        }
    }

    /**
     * Assert valid url path.
     * 
     * PHP doesn't actually have a URL path validation function but it does have a URL validation function.
     * So, to validate a path all we need to prepend any domain onto path.
     * 
     * Note: to be a path it should start with a forward slash. 
     * 
     * @param string $value
     * 
     * @return void
     */
    public static function urlPath($value, $message = ''): void
    {
        if ($value[0] !== '/' || filter_var('http://foo.com' . $value, FILTER_VALIDATE_URL) === false) {
            static::reportInvalidArgument(sprintf($message ?: 'Expected valid url path. Got: %s', $value));
        }
    }

    /**
     * Assert string for hex color.
     * 
     * This will match an arbitrary hexadecimal color value that can be used in CSS, such as #91bf4a or #f13.
     * 
     * Note: No support for RGBA hex color values, though.
     *
     * @param string $value
     * @see https://stackoverflow.com/questions/1636350/how-to-identify-a-given-string-is-hex-color-format
     */
    public static function hexColor($value, $message = ''): void
    {
        if (!preg_match('/^#(?:[0-9a-f]{3}){1,2}$/ui', $value)) {
            static::reportInvalidArgument(
                sprintf(
                    $message ?: 'Expected value is hex color. Got: %s',
                    static::valueToString($value)
                )
            );
        }
    }

    /**
     * @throws BadMethodCallException
     */
    public static function __callStatic($name, $arguments)
    {
        if ('emptyOr' === substr($name, 0, 7)) {
            if (!empty($arguments[0])) {
                $method = lcfirst(substr($name, 7));
                call_user_func_array(['static', $method], $arguments);
            }

            return;
        }

        parent::__callStatic($name, $arguments);
    }
}