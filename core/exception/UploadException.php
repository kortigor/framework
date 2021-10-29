<?php

declare(strict_types=1);

namespace core\exception;

class UploadException extends BaseException
{
    public function __construct($code)
    {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'File upload problem';
    }

    private function codeToMessage($code)
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension",
            default => "Unknown upload error"
        };
    }
}