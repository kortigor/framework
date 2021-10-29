<?php

declare(strict_types=1);

namespace core\web;

use SimpleXMLElement;
use RuntimeException;
use Closure;
use core\traits\GetSetByPropsTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @property-read CookieCollection $cookies Request cookie collection (readonly).
 */
final class ServerRequest extends \core\http\ServerRequest implements ServerRequestInterface
{
    use GetSetByPropsTrait;

    /**
     * @var array
     */
    private array $bodyParsers = [];

    /**
     * @var CookieCollection
     */
    private CookieCollection $_cookies;

    /**
     * Constructor.
     * 
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array<string, string|string[]> $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     * @param array $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function (string $input): array {
            parse_str($input, $data);
            return $data;
        });

        $this->registerMediaTypeParser('application/json', function (string $input): ?array {
            $result = json_decode($input, true);
            return is_array($result) ? $result : null;
        });

        $xmlParser = function (string $input): ?SimpleXMLElement {
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);

            return $result === false ? null : $result;
        };
        $this->registerMediaTypeParser('application/xml', $xmlParser);
        $this->registerMediaTypeParser('text/xml', $xmlParser);

        parent::__construct($method, $uri, $headers, $body, $version, $serverParams);
    }

    /**
     * Disable magic setter to ensure immutability.
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
     * Returns the cookie collection.
     *
     * Through the returned cookie collection, you may access a cookie using the following syntax:
     *
     * ```php
     * $cookie = $request->cookies['name']
     * if ($cookie !== null) {
     *     $value = $cookie->value;
     * }
     *
     * // alternatively
     * $value = $request->cookies->getValue('name');
     * ```
     *
     * @return CookieCollection the cookie collection.
     */
    public function getCookiesAttribute()
    {
        if (!isset($this->_cookies)) {
            $this->_cookies = new CookieCollection($this->loadCookies(), true);
        }

        return $this->_cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        $parsedBody = parent::getParsedBody();

        if (!empty($parsedBody)) {
            return $parsedBody;
        }

        $mediaType = $this->getMediaType();
        if ($mediaType === null) {
            return $parsedBody;
        }

        // Check if this specific media type has a parser registered first
        if (!isset($this->bodyParsers[$mediaType])) {
            // If not, look for a media type with a structured syntax suffix (RFC 6839)
            $parts = explode('+', $mediaType);
            if (count($parts) >= 2) {
                $mediaType = 'application/' . $parts[count($parts) - 1];
            }
        }

        if (isset($this->bodyParsers[$mediaType])) {
            $body = (string)$this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            return $parsed;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        $queryParams = parent::getQueryParams();

        if (is_array($queryParams) && !empty($queryParams)) {
            return $queryParams;
        }

        $parsedQueryParams = [];
        parse_str($this->getUri()->getQuery(), $parsedQueryParams);

        return $parsedQueryParams;
    }

    /**
     * Create a new instance with the specified derived request attributes.
     *
     * This method allows setting all new derived request attributes as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attributes.
     *
     * @param array $attributes New attributes
     * @return static
     */
    public function withAttributes(array $attributes): static
    {
        foreach ($attributes as $attribute => $value) {
            $serverRequest = $this->withAttribute($attribute, $value);
        }

        return $serverRequest;
    }

    /**
     * Get serverRequest content character set, if known.
     *
     * @return string|null
     */
    public function getContentCharset(): ?string
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get serverRequest content type.
     *
     * @return string|null The serverRequest content type, if known
     */
    public function getContentType(): ?string
    {
        $result = $this->getHeader('Content-Type');
        return $result[0] ?? null;
    }

    /**
     * Get serverRequest content length, if known.
     *
     * @return int|null
     */
    public function getContentLength(): ?int
    {
        $result = $this->getHeader('Content-Length');
        return $result ? (int) $result[0] : null;
    }

    /**
     * Fetch cookie value from cookies sent by the client to the server.
     *
     * @param string $key The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     *
     * @return mixed
     */
    public function getCookieParam(string $key, $default = null): mixed
    {
        $cookies = $this->getCookieParams();
        if (isset($cookies[$key])) {
            $result = $cookies[$key];
        }

        return $result ?? $default;
    }

    /**
     * Get serverRequest media type, if known.
     *
     * @return string|null The serverRequest media type, minus content-type params
     */
    public function getMediaType(): ?string
    {
        $contentType = $this->getContentType();

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            if ($contentTypeParts === false) {
                return null;
            }
            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get serverRequest media type params, if known.
     *
     * @return mixed[]
     */
    public function getMediaTypeParams(): array
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            if ($contentTypeParts !== false) {
                $contentTypePartsLength = count($contentTypeParts);
                for ($i = 1; $i < $contentTypePartsLength; $i++) {
                    $paramParts = explode('=', $contentTypeParts[$i]);
                    $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
                }
            }
        }

        return $contentTypeParams;
    }

    /**
     * Fetch serverRequest parameter value from body or query string (in that order).
     *
     * @param string $key The parameter key.
     * @param mixed  $default The default value.
     *
     * @return mixed The parameter value.
     */
    public function getParam(string $key, $default = null): mixed
    {
        $bodyParams = $this->getParsedBody();
        $queryParams = $this->getQueryParams();

        if (is_array($bodyParams) && isset($bodyParams[$key])) {
            $result = $bodyParams[$key];
        } elseif (is_object($bodyParams) && property_exists($bodyParams, $key)) {
            $result = $bodyParams->$key;
        } elseif (isset($queryParams[$key])) {
            $result = $queryParams[$key];
        }

        return $result ?? $default;
    }

    /**
     * Fetch associative array of body and query string parameters.
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        $params = $this->getQueryParams();
        $bodyParams = $this->getParsedBody();

        if ($bodyParams) {
            $params = array_merge($params, (array)$bodyParams);
        }

        return $params;
    }

    /**
     * Fetch parameter value from serverRequest body.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParsedBodyParam(string $key, $default = null): mixed
    {
        $bodyParams = $this->getParsedBody();
        if (is_array($bodyParams) && isset($bodyParams[$key])) {
            $result = $bodyParams[$key];
        } elseif (is_object($bodyParams) && property_exists($bodyParams, $key)) {
            $result = $bodyParams->$key;
        }

        return $result ?? $default;
    }

    /**
     * Fetch parameter value from query string.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getQueryParam(string $key, $default = null): mixed
    {
        $queryParams = $this->getQueryParams();
        if (isset($queryParams[$key])) {
            $result = $queryParams[$key];
        }

        return $result ?? $default;
    }

    /**
     * Retrieve a server parameter.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getServerParam(string $key, $default = null): ?string
    {
        $serverParams = $this->getServerParams();
        return $serverParams[$key] ?? $default;
    }

    /**
     * Register media type parser.
     *
     * @param string $mediaType HTTP media type (excluding content-type params).
     * @param callable $callable Callable that returns parsed contents for media type.
     * @return static
     */
    public function registerMediaTypeParser(string $mediaType, callable $callable): ServerRequestInterface
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }

        $this->bodyParsers[$mediaType] = $callable;

        return $this;
    }

    /**
     * Get HTTP referrer.
     * 
     * @return string|null
     */
    public function getReferrer(): ?string
    {
        return $this->getServerParam('HTTP_REFERER');
    }

    /**
     * Get parameter value from $_GET
     * 
     * @param string|null $name Query parameter name
     * @param mixed $default Query value to return if parameter with given name not set
     * 
     * @return mixed
     *  - setted `$name`: parameter value or `$default` value if parameter not set
     *  - unsetted `$name`: the whole `$_GET` array
     */
    public function get(string $name = null, $default = null): mixed
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        return $this->getQueryParam($name, $default);
    }

    /**
     * Get parameter value from $_POST
     * 
     * @param string|null $name Parameter name.
     * @param mixed $default Value to return if parameter with given name not set.
     * 
     * @return mixed
     *  - setted `$name`: parameter value or `$default` value if parameter not set;
     *  - unsetted `$name`: the whole `$_POST` array.
     */
    public function post(string $name = null, $default = null): mixed
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->getParsedBodyParam($name, $default);
    }

    /**
     * Get value from upload metadata. These prepared from $_FILES.
     * An array tree of UploadedFileInterface instances; an empty array MUST be returned if no data is present.
     * Get uploaded data.
     * 
     * @return UploadedFileInterface|array|null
     *  - setted `$name`: parameter value or `$default` value if parameter not set;
     *  - unsetted `$name`: the whole uploaded files metadata array.
     * @see ServerRequest::getUploadedFiles()
     */
    public function files(string $name = null, $default = null): UploadedFileInterface|array|null
    {
        $data = $this->getUploadedFiles();
        if ($name === null) {
            return $data;
        }

        return $data[$name] ?? $default;
    }

    /**
     * Does this serverRequest use a given method?
     *
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === $method;
    }

    /**
     * Is this a DELETE serverRequest?
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a GET serverRequest?
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a HEAD serverRequest?
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS serverRequest?
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this a PATCH serverRequest?
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a POST serverRequest?
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT serverRequest?
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this an Ajax (XHR) serverRequest?
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Returns whether this is a PJAX request.
     * 
     * @return bool whether this is a PJAX request
     */
    public function isPjax(): bool
    {
        return $this->isAjax() && $this->hasHeader('X-Pjax');
    }

    /**
     * Check possible XSS attacks via $_GET.
     * 
     * @param string|array $url
     * 
     * @return void
     * @throws RuntimeException if XSS attack detected.
     */
    public function checkXss(string|array $url): void
    {
        if (is_array($url)) {
            foreach ($url as $value) {
                $this->checkXss($value);
            }
        } else {
            $url = str_replace(['"', "'"], ['', ''], urldecode($url));
            if (preg_match('/<[^<>]+>/i', $url)) {
                throw new RuntimeException('Possible XSS attack...');
            }
        }
    }

    /**
     * Converts cookie params into an array of `\core\web\Cookie` objects.
     * 
     * @return array Converted cookies.
     */
    protected function loadCookies(): array
    {
        foreach ($this->getCookieParams() as $name => $value) {
            if (is_string($value)) {
                $cookies[$name] = new Cookie($name, $value);
            }
        }

        return $cookies ?? [];
    }
}