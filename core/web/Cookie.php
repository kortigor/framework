<?php

declare(strict_types=1);

namespace core\web;

use InvalidArgumentException;

/**
 * Cookie class represents implementation to work with a cookie.
 */
class Cookie
{
    /**
     * SameSite policy Lax will prevent the cookie from being sent by the browser in all cross-site browsing context
     * during CSRF-prone request methods (e.g. POST, PUT, PATCH etc).
     * E.g. a POST request from https://otherdomain.com to https://yourdomain.com will not include the cookie, however a GET request will.
     * When a user follows a link from https://otherdomain.com to https://yourdomain.com it will include the cookie
     * @see $sameSite
     */
    const SAME_SITE_LAX = 'Lax';
    /**
     * SameSite policy Strict will prevent the cookie from being sent by the browser in all cross-site browsing context
     * regardless of the request method and even when following a regular link.
     * E.g. a GET request from https://otherdomain.com to https://yourdomain.com or a user following a link from
     * https://otherdomain.com to https://yourdomain.com will not include the cookie.
     * @see $sameSite
     */
    const SAME_SITE_STRICT = 'Strict';

    const SAME_SITE_NONE = 'None';

    /**
     * @var string name of the cookie
     */
    private string $name;
    /**
     * @var string value of the cookie
     */
    private string $value = '';
    /**
     * @var string domain of the cookie
     */
    private string $domain = '';
    /**
     * @var int The UNIX timestamp at which the cookie expires. This is the server timestamp.
     * If 0, it meaning "until the browser is closed".
     * 
     * Request Cookies collection represents the cookies included in the request's cookie header (HTTP 'Cookie' header).
     * Such cookies do not contain any info regarding when they expire
     * (and anything else besides name and value, like HttpOnly / Secure flags):
     * the browser simply does not send anything but the cookie name and value to the server.
     * 
     * The Expires value (and the rest of cookie properties) only makes sense when adding cookies to the response
     * (which sets the HTTP 'Set-Cookie' header).
     */
    private int $expires = 0;
    /**
     * @var string the path on the server in which the cookie will be available on. The default is '/'.
     */
    private string $path = '/';
    /**
     * @var bool whether cookie should be sent via secure connection
     */
    private bool $secure = false;
    /**
     * @var bool whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    private bool $httpOnly = true;
    /**
     * @var string SameSite prevents the browser from sending this cookie along with cross-site requests.
     * See https://web.dev/samesite-cookies-explained/ for more information about sameSite.
     */
    private string $sameSite = self::SAME_SITE_LAX;

    /**
     * Constructor.
     * 
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expires Cookie expire time
     */
    public function __construct(string $name, string $value = '', int $expires = 0)
    {
        self::assertName($name);
        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
    }

    /**
     * Sets a new cookie in a way compatible to PHP's `setcookie(...)` function
     *
     * @param string $name the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     * @param string $value the value of the cookie that will be stored on the client's machine
     * @param int $expires the Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds`
     * @param string $path the path on the server that the cookie will be valid for (including all sub-directories),
     * e.g. an empty string for the current directory or `/` for the root directory
     * @param string $domain the domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
     * @param bool $secure indicates that the cookie should be sent back by the client over secure HTTPS connections only
     * @param bool $httpOnly indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages
     * @param string $sameSite indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     * 
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to set the cookie)
     * 
     * @throws InvalidArgumentException If cookie name or expire time is invalid.
     */
    public static function setcookie(
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = self::SAME_SITE_LAX
    ): bool {
        self::assertName($name);
        self::assertExpiryTime($expires);
        return setcookie($name, $value, self::buildOptions($expires, $path, $domain, $secure, $httpOnly, $sameSite));
    }

    /**
     * Checks whether a cookie with the specified name exists
     *
     * @param string $name the name of the cookie to check
     * @return bool whether there is a cookie with the specified name
     */
    public static function exists(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Get cookie value with given name.
     * 
     * @param string $name cookie name.
     * 
     * @return mixed value from the requested cookie or the default value.
     */
    public static function get(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Unset cookie with given name
     * 
     * @param string $name
     * 
     * @return void
     */
    public static function unset(string $name): void
    {
        setcookie($name, '', 1, '/');
        unset($_COOKIE[$name]);
    }

    /**
     * Magic method to turn a cookie object into a string
     *
     * ```php
     * if (isset($request->cookies['name'])) {
     *     $value = (string) $request->cookies['name'];
     * }
     * ```
     *
     * @return string The value of the cookie. If the value property is null, an empty string will be returned.
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Returns the name of the cookie
     *
     * @return string the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the cookie.
     *
     * @param string $name the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     * @return self this instance for chaining
     * @throws InvalidArgumentException If cookie name is invalid.
     */
    public function setName(string $name): self
    {
        self::assertName($name);
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the value of the cookie
     *
     * @return string the value of the cookie that will be stored on the client's machine
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the value for the cookie
     *
     * @param string $value the value of the cookie that will be stored on the client's machine
     * @return self this instance for chaining
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Returns the expire time of the cookie
     *
     * @return int|null the Unix timestamp indicating the time that the cookie will expire at,
     * i.e. usually `time() + $seconds`
     */
    public function getExpiryTime(): ?int
    {
        return $this->expires;
    }

    /**
     * Sets the expire time for the cookie
     *
     * @param int $timestamp the Unix timestamp indicating the time that the cookie will expire at,
     * i.e. usually `time() + $seconds`
     * @return self this instance for chaining
     */
    public function setExpiryTime(int $timestamp): self
    {
        $this->expires = $timestamp;
        return $this;
    }

    /**
     * Returns the maximum age of the cookie (i.e. the remaining lifetime)
     *
     * @return int the maximum age of the cookie in seconds
     */
    public function getMaxAge(): int
    {
        return (int) $this->expires - time();
    }

    /**
     * Sets the expire time for the cookie based on the specified maximum age (i.e. the remaining lifetime)
     *
     * @param int $maxAge the maximum age for the cookie in seconds
     * @return self this instance for chaining
     * 
     * @throws InvalidArgumentException If cookie expire time is invalid.
     */
    public function setMaxAge(int $maxAge): self
    {
        self::assertExpiryTime($maxAge);
        $this->expires = time() + $maxAge;
        return $this;
    }

    /**
     * Returns the path of the cookie
     *
     * @return string the path on the server that the cookie will be valid for (including all sub-directories),
     * e.g. an empty string for the current directory or `/` for the root directory
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets the path for the cookie
     *
     * @param string $path the path on the server that the cookie will be valid for (including all sub-directories),
     * e.g. an empty string for the current directory or `/` for the root directory
     * @return self this instance for chaining
     */
    public function setPath($path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns the domain of the cookie
     *
     * @return string the domain that the cookie will be valid for (including subdomains) or `null`
     * for the current host (excluding subdomains)
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Sets the domain for the cookie
     *
     * @param string $domain the domain that the cookie will be valid for (including subdomains)
     * or `null` for the current host (excluding subdomains)
     * @return self this instance for chaining
     */
    public function setDomain(string $domain = null): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Sets whether the cookie should be accessible through HTTP only
     *
     * @param bool $httpOnly indicates that the cookie should be accessible through the HTTP protocol
     * only and not through scripting languages
     * @return self this instance for chaining
     */
    public function setHttpOnly($httpOnly): self
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    /**
     * Sets whether the cookie should be sent over HTTPS only
     *
     * @param bool $secureOnly indicates that the cookie should be sent back by the client
     * over secure HTTPS connections only
     * @return self this instance for chaining
     */
    public function setSecureOnly(bool $secureOnly): self
    {
        $this->secure = $secureOnly;
        return $this;
    }

    /**
     * Returns the same-site restriction of the cookie
     *
     * @return string whether the cookie should not be sent along with cross-site
     * requests (either `null`, `None`, `Lax` or `Strict`)
     */
    public function getSameSite(): string
    {
        return $this->same;
    }

    /**
     * Sets the same-site restriction for the cookie
     *
     * @param string|null $sameSite indicates that the cookie should not be sent along
     * with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     * @return self this instance for chaining
     */
    public function setSameSite(?string $sameSite): self
    {
        $this->same = $sameSite;
        return $this;
    }

    /**
     * Returns whether the cookie should be sent over HTTPS only
     *
     * @return bool whether the cookie should be sent back by the client over secure HTTPS connections only
     */
    public function isSecureOnly(): bool
    {
        return $this->secure;
    }

    /**
     * Returns whether the cookie should be accessible through HTTP only
     *
     * @return bool whether the cookie should be accessible through the HTTP protocol only and not through scripting languages
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Is cookie expired?
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires !== 0 && $this->expires < time();
    }

    /**
     * Set cookie.
     * 
     * @return bool
     */
    public function set(): bool
    {
        $options = self::buildOptions(
            $this->expires,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly,
            $this->sameSite
        );
        return setcookie($this->name, $this->value, $options);
    }

    /**
     * Remove cookie by set outdated expire.
     * 
     * @return bool
     */
    public function remove(): bool
    {
        $this->value = '';
        $this->expires = 1;
        $this->domain = '/';
        return $this->set();
    }

    private static function assertName($name)
    {
        // The name of a cookie must not be empty on PHP 7+ (https://bugs.php.net/bug.php?id=69523).
        if ($name === '' || preg_match('/[=,; \\t\\r\\n\\013\\014]/', $name)) {
            throw new InvalidArgumentException("Cookie name {$name} is invalid");
        }
    }

    private static function assertExpiryTime($time)
    {
        if ((!is_int($time) && !is_null($time)) || $time < 0) {
            throw new InvalidArgumentException("Cookie expire time {$time} is invalid");
        }
    }

    private static function buildOptions(
        int $expires,
        string $path,
        string $domain,
        bool $secure,
        bool $httponly,
        string $samesite
    ): array {
        return compact('expires', 'path', 'domain', 'secure', 'httponly', 'samesite');
    }
}