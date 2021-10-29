<?php

declare(strict_types=1);

namespace core\exception;

abstract class BaseException extends \Exception
{
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * Add text to the end of error message
     * 
     * @param string $txt
     * 
     * @return void
     */
    public function appendToMessage(string $txt): void
    {
        $this->message .= $txt;
    }

    /**
     * Add text to the begin of error message
     * 
     * @param string $txt
     * 
     * @return void
     */
    public function prependToMessage(string $txt): void
    {
        $this->message = $txt . $this->message;
    }
}