<?php

declare(strict_types=1);

namespace core\runner\emitter;

use Psr\Http\Message\ResponseInterface;
use core\web\Response;
use core\web\ContentType;
use core\web\Cookie;

class Emitter implements EmitterInterface
{
    use SapiEmitterTrait;

    /**
     * @var bool Whether the response has been sent. If this is true, calling `emit()` will do nothing.
     */
    public bool $isSent = false;

    /**
     * @var int Output buffer chunk size
     */
    public int $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

    /**
     * Sends the response to the client.
     * 
     * @param ResponseInterface $response Response to emit
     * 
     * @return bool
     */
    public function emit(ResponseInterface $response): bool
    {
        if ($this->isSent) {
            return false;
        }

        $this->assertNoPreviousOutput();
        $this->emitResponseHeaders($response);
        $this->emitResponseContent($response);
        $this->isSent = true;

        return true;
    }

    /**
     * Sends the response headers to the client.
     * 
     * @param ResponseInterface $response
     * 
     * @return mixed
     */
    protected function emitResponseHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            // set replace for first occurrence of header but false afterwards to allow multiple
            $replace = true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $replace);
                $replace = false;
            }
        }

        $statusCode = $response->getStatusCode();
        $statusText = $response->getReasonPhrase();
        $version = $response->getProtocolVersion();
        header(sprintf('HTTP/%s %d %s', $version, $statusCode, $statusText), true, $statusCode);
        $this->emitResponseCookies($response);
    }

    /**
     * Sends the cookies to the client.
     * 
     * @param ResponseInterface $response
     * 
     * @return void
     */
    protected function emitResponseCookies(ResponseInterface $response): void
    {
        /** @var Response $response */
        /** @var Cookie $cookie */
        foreach ($response->cookies as $cookie) {
            $cookie->set();
        }
    }

    /**
     * Sends the response content to the client.
     * 
     * @param ResponseInterface $response
     * 
     * @return void
     */
    protected function emitResponseContent(ResponseInterface $response): void
    {
        /** @var Response $response */
        if ($response->getFormat() === ContentType::FORMAT_RAW) {
            set_time_limit(0); // Reset time limit for raw, i.e. files
        }

        $body = $response->getBody();
        $body->rewind(); // ensure pointer on start of stream
        while (!$body->eof()) {
            echo $body->read($this->chunkSize);
        }
        $body->close();
    }
}