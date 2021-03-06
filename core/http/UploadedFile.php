<?php

declare(strict_types=1);

namespace core\http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\{
    StreamInterface,
    UploadedFileInterface
};


class UploadedFile implements UploadedFileInterface
{
    private const ERRORS = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    /**
     * @var string|null
     */
    private ?string $clientFilename;

    /**
     * @var string|null
     */
    private ?string $clientMediaType;

    /**
     * @var int
     */
    private int $error;

    /**
     * @var string|null
     */
    private ?string $file;

    /**
     * @var bool
     */
    private bool $moved = false;

    /**
     * @var int|null
     */
    private ?int $size;

    /**
     * @var StreamInterface|null
     */
    private ?StreamInterface $stream;

    /**
     * @param StreamInterface|string|resource $streamOrFile
     */
    public function __construct($streamOrFile, ?int $size, int $errorStatus, string $clientFilename = null, string $clientMediaType = null)
    {
        $this->setError($errorStatus);
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    public function __toString()
    {
        return $this->clientFilename;
    }

    /**
     * Depending on the value set file or stream variable
     *
     * @param StreamInterface|string|resource $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile): void
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException(
                'Invalid stream or file provided for UploadedFile'
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function setError(int $error): void
    {
        if (false === in_array($error, UploadedFile::ERRORS, true)) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile'
            );
        }

        $this->error = $error;
    }

    private function isStringNotEmpty($param): bool
    {
        return is_string($param) && false === empty($param);
    }

    /**
     * Return true if there is no upload error
     */
    private function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function isMoved(): bool
    {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive(): void
    {
        if (false === $this->isOk()) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->isMoved()) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        /** @var string $file */
        $file = $this->file;

        return new LazyOpenStream($file, 'r+');
    }

    public function moveTo($targetPath): bool
    {
        $this->validateActive();

        if (false === $this->isStringNotEmpty($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->file) {
            $this->moved = PHP_SAPI === 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
            Utils::copyToStream(
                $this->getStream(),
                new LazyOpenStream($targetPath, 'w')
            );

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }

        return $this->moved;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Get file extension
     * 
     * @return string file extension
     */
    public function getExtension(): ?string
    {
        return strtolower(pathinfo($this->clientFilename, PATHINFO_EXTENSION));
    }
}
