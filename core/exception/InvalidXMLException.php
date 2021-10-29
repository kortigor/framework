<?php

declare(strict_types=1);

namespace core\exception;

use LibXMLError;

/**
 * InvalidXMLException represents an exception caused by incorrect XML file.
 */
class InvalidXMLException extends BaseException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Invalid XML file';
    }

    /**
     * Create exception from errors returned by libxml_get_errors()
     * 
     * @return self
     */
    public static function fromXmlErrors(): self
    {
        $messages = array_map(
            function (LibXMLError $error) {
                $message = str_replace(["\n", "\r", "\n\r", "\r\n", "\t"], '', $error->message);
                return sprintf('"%s", at line: %u', $message, $error->line);
            },
            libxml_get_errors()
        );

        return new self(implode("\n", $messages));
    }
}
