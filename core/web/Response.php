<?php

declare(strict_types=1);

namespace core\web;

use SimpleXMLElement;
use InvalidArgumentException;
use RuntimeException;
use core\traits\GetSetByPropsTrait;
use core\interfaces\UnformattedResponse;
use core\http\HttpFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @property CookieCollection $cookies Response cookie collection.
 */
final class Response extends \core\http\Response implements ResponseInterface, UnformattedResponse
{
    use GetSetByPropsTrait;

    /**
     * EOL characters used for HTTP response.
     *
     * @var string
     */
    private const EOL = "\r\n";

    /**
     * @var string the response format. This determines how to convert `$data` into response content
     */
    private string $format = ContentType::FORMAT_HTML;

    /**
     * @var mixed The original response data. When this is not null, it will be converted into `$stream`
     * according to `$format` when the response is being sent out.
     */
    private $bodyData = null;

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @var string The charset of the text response.
     */
    private string $charset;

    /**
     * @var CookieCollection
     */
    private CookieCollection $cookie;

    /**
     * @var StreamFactoryInterface
     */
    private StreamFactoryInterface $streamFactory;

    public function __construct(StreamFactoryInterface $streamFactory, string $charset = 'UTF-8')
    {
        $this->streamFactory = $streamFactory;
        $this->charset = $charset;
        parent::__construct();
    }

    /**
     * Create new empty response
     * 
     * @return self
     */
    public static function createNew(): UnformattedResponse
    {
        return new self(new HttpFactory());
    }

    /**
     * Disable magic setter to ensure immutability
     * 
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        return;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute(string $attribute, $value): ResponseInterface
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (array_key_exists($attribute, $this->attributes) === false) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritDoc}
     */
    public function withoutAttribute(string $attribute): ResponseInterface
    {
        if (array_key_exists($attribute, $this->attributes) === false) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withBodyData($value): ResponseInterface
    {
        $new = clone $this;
        $new->bodyData = $value;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutBodyData(): ResponseInterface
    {
        $new = clone $this;
        $new->bodyData = null;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getBodyData()
    {
        return $this->bodyData;
    }

    /**
     * {@inheritDoc}
     */
    public function hasBodyData(): bool
    {
        return $this->bodyData !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function withFormat(string $format): ResponseInterface
    {
        $new = clone $this;
        $new->format = $format;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Set response status code. Mutable.
     * 
     * @param int $statusCode
     * @param string $reasonPhrase
     * 
     * @return self
     */
    public function setStatusCode(int $statusCode, string $reasonPhrase = ''): self
    {
        $this->statusCode = $statusCode;
        if ($reasonPhrase === '' && isset(self::HTTP_STATUSES[$statusCode])) {
            $reasonPhrase = self::HTTP_STATUSES[$statusCode];
        }
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    /**
     * Write JSON to Response Body.
     *
     * This method prepares the response object to return an HTTP Json
     * response to the client.
     *
     * @param mixed $data The data which have to json_encode.
     * @param int|null $status The HTTP status code
     * @param int $options Json encoding options
     * @param int $depth Json encoding max depth
     * 
     * @return static
     * @throws RuntimeException if json encoding fails
     */
    public function withJson($data, int $options = 0, int $depth = 512): ResponseInterface
    {
        $json = json_encode($data, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        $response = $this->withHeader('Content-Type', 'application/json; charset=' . $this->charset)
            ->withBody($this->streamFactory->createStream($json));

        return $response;
    }

    /**
     * Write XML to Response Body.
     *
     * This method prepares the response object to return an Xml response to the client.
     * @param SimpleXMLElement $data object contains xml data to send.
     * 
     * @return ResponseInterface
     * @throws RuntimeException if decoding fails
     */
    public function withXml(SimpleXMLElement $data): ResponseInterface
    {
        $xml = $data->asXML();

        if ($xml === false) {
            throw new RuntimeException('Invalid SimpleXMLElement object');
        }

        $response = $this->withHeader('Content-Type', 'text/xml; charset=' . $this->charset)
            ->withBody($this->streamFactory->createStream($xml));

        return $response;
    }

    /**
     * Write HTML to Response Body.
     *
     * This method prepares the response object to return an HTML response to the client.
     * 
     * @param string $data
     * 
     * @return ResponseInterface
     */
    public function withHtml(string $data): ResponseInterface
    {
        $response = $this->withHeader('Content-Type', 'text/html; charset=' . $this->charset)
            ->withBody($this->streamFactory->createStream($data));

        return $response;
    }

    /**
     * This method will trigger the client to download the specified file
     * It will append the `Content-Disposition` header to the response object
     *
     * @param string|resource|StreamInterface $file
     * @param string|null $name
     * @param string $contentType
     *
     * @return static
     */
    public function withFileDownload($file, string $name = null, string $contentType = ''): ResponseInterface
    {
        $disposition = 'attachment';
        $fileName = $name;

        if (is_string($file) && $name === null) {
            $fileName = basename($file);
        }

        if ($name === null && (is_resource($file) || $file instanceof StreamInterface)) {
            $metaData = $file instanceof StreamInterface ? $file->getMetadata() : stream_get_meta_data($file);

            if (is_array($metaData) && isset($metaData['uri'])) {
                $uri = $metaData['uri'];
                if ('php://' !== substr($uri, 0, 6)) {
                    $fileName = basename($uri);
                }
            }
        }

        if (is_string($fileName) && strlen($fileName)) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        return $this->withFile($file, $contentType)->withHeader('Content-Disposition', $disposition);
    }

    /**
     * This method prepares the response object to return a file response to the
     * client without `Content-Disposition` header which defaults to `inline`
     *
     * You control the behavior of the `Content-Type` header declaration via `$contentType`
     * Use a string to override the header to a value of your choice. e.g.: `application/json`
     * When set to `true` we attempt to detect the content type using `mime_content_type`
     * When set to `false`
     *
     * @param string|resource|StreamInterface $file
     * @param string $contentType
     *
     * @return static
     *
     * @throws RuntimeException If the file cannot be opened.
     * @throws InvalidArgumentException If the mode is invalid.
     */
    public function withFile(string $file, string $contentType = ''): ResponseInterface
    {
        if (is_resource($file)) {
            $response = $this->withBody($this->streamFactory->createStreamFromResource($file));
        } elseif (is_string($file)) {
            $response = $this->withBody($this->streamFactory->createStreamFromFile($file));
        } elseif ($file instanceof StreamInterface) {
            $response = $this->withBody($file);
        } else {
            throw new InvalidArgumentException(
                'Parameter 1 of Response::withFile() must be resource, string or an instance of ' . StreamInterface::class
            );
        }

        if ($contentType === '') {
            $contentType = is_string($file) ? mime_content_type($file) : 'application/octet-stream';
        }

        $response = $response->withHeader('Content-Type', $contentType);
        return $response;
    }

    /**
     * Redirect to specified location
     *
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     * 
     * 301 Moved Permanently, to be cached by browser
     * 302 Moved Temporarily, not cacheng by browser
     *
     * @param string $url The redirect destination.
     * @param int $status The redirect HTTP status code.
     * @return static
     */
    public function withRedirect(string $url, int $status = 302): ResponseInterface
    {
        $response = $this->withStatus($status)->withHeader('Location', $url);
        return $response;
    }

    /**
     * Add headers to prevent browser caching.
     * 
     * @return ResponseInterface
     */
    public function withNoCache(): ResponseInterface
    {
        $response = $this->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
            ->withHeader('Pragma', 'no-cache');
        return $response;
    }

    /**
     * Write data to the response body.
     *
     * @param string $data
     * @return static
     */
    public function write(string $data): ResponseInterface
    {
        $this->getBody()->write($data);
        return $this;
    }

    /**
     * Returns the cookie collection.
     *
     * Through the returned cookie collection, you can add or remove cookies as follows,
     *
     * ```php
     * // add a cookie
     * $response->cookies->add(new Cookie($name, $value));
     *
     * // remove a cookie
     * $response->cookies->removeByName('name');
     * $response->cookies->remove(new Cookie($name, $value));
     * 
     * // alternatively
     * unset($response->cookies['name']);
     * ```
     *
     * @return CookieCollection the cookie collection.
     */
    public function getCookiesAttribute(): CookieCollection
    {
        if (!isset($this->cookie)) {
            $this->cookie = new CookieCollection;
        }

        return $this->cookie;
    }

    /**
     * Is this response a client error?
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->response->getStatusCode() < 500;
    }

    /**
     * Is this response empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->getStatusCode(), [201, 204, 205, 304]);
    }

    /**
     * Is this response forbidden?
     *
     * @return bool
     * @api
     */
    public function isForbidden(): bool
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response informational?
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is this response not Found?
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a redirect?
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307, 308]);
    }

    /**
     * Is this response a redirection?
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response a server error?
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Is this response successful?
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response invalid?
     * 
     * @return bool
     */
    public function isInvalid(): bool
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * Convert response to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = sprintf(
            'HTTP/%s %s %s%s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase(),
            self::EOL
        );

        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . self::EOL;
        }

        $output .= self::EOL;
        $output .= (string) $this->getBody();

        return $output;
    }

    /**
     * Clears the headers, cookies, content, status code of the response.
     */
    public function clear()
    {
        $this->headers = [];
        $this->headerNames = [];
        $this->statusCode = 200;
        $this->reasonPhrase = 'OK';
        $this->stream = null;
        $this->data = null;
        $this->cookie = new CookieCollection;
        $this->attributes = [];
    }
}