<?php

declare(strict_types=1);

namespace core\exception;

/**
 * ErrorException represents a PHP error.
 */
class ErrorException extends \ErrorException
{
    /**
     * User-friendly names of this exception.
     * @var array
     */
    protected const NAMES = [
        E_COMPILE_ERROR => 'PHP Compile Error',
        E_COMPILE_WARNING => 'PHP Compile Warning',
        E_CORE_ERROR => 'PHP Core Error',
        E_CORE_WARNING => 'PHP Core Warning',
        E_DEPRECATED => 'PHP Deprecated Warning',
        E_ERROR => 'PHP Fatal Error',
        E_NOTICE => 'PHP Notice',
        E_PARSE => 'PHP Parse Error',
        E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
        E_STRICT => 'PHP Strict Warning',
        E_USER_DEPRECATED => 'PHP User Deprecated Warning',
        E_USER_ERROR => 'PHP User Error',
        E_USER_NOTICE => 'PHP User Notice',
        E_USER_WARNING => 'PHP User Warning',
        E_WARNING => 'PHP Warning',
    ];

    /**
     * @var array
     */
    protected const FATAL_ERRORS = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];

    /**
     * Constructor.
     * 
     * @param string $message
     * @param int $code
     * @param int $severity
     * @param string|null $filename
     * @param int|null $lineno
     * @param \Exception|null $previous
     * 
     * @link https://secure.php.net/manual/en/errorexception.construct.php
     */
    public function __construct($message = '', $code = 0, $severity = 1, $filename = __FILE__, $lineno = __LINE__, \Exception $previous = null)
    {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
    }

    /**
     * Whether error is one of fatal type.
     *
     * @param array $error error got from error_get_last()
     * @return bool if error is one of fatal type
     */
    public static function isFatalError(array $error): bool
    {
        return in_array($error['type'] ?? false, static::FATAL_ERRORS);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return static::NAMES[$this->getCode()] ?? 'Error';
    }
}