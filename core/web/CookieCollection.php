<?php

declare(strict_types=1);

namespace core\web;

use core\exception\InvalidCallException;
use core\collections\Collection;
use core\validators\Assert;

/**
 * CookieCollection maintains the cookies available in the current request.
 */
class CookieCollection extends Collection
{
    /**
     * @var string[] Cookie names which can not be deleted of replaced even if collection not readonly.
     */
    private array $guarded = [];

    /**
     * Constructor.
     * 
     * @param Cookie[] $data Cookie objects collection
     * @param bool $readOnly Whether this collection is read only.
     */
    public function __construct(array $data = [], private bool $readOnly = false)
    {
        $this->assertIsCookies($data);
        parent::__construct($data);
    }

    /**
     * Returns the cookie with the specified name.
     * 
     * @param string $name the cookie name
     * @return Cookie|null the cookie with the specified name. Null if the named cookie does not exist.
     * @see getValue()
     */
    public function get(string $name): ?Cookie
    {
        return $this->offsetGet($name);
    }

    /**
     * Returns the value of the named cookie.
     * 
     * @param string $name the cookie name
     * @param mixed $defaultValue the value that should be returned when the named cookie does not exist.
     * @return mixed the value of the named cookie.
     * @see get()
     */
    public function getValue(string $name, $defaultValue = null)
    {
        if (!$this->has($name)) {
            return $defaultValue;
        }

        return $this->get($name)->getValue();
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * 
     * Note that if a cookie is marked for deletion from browser, this method will return false.
     * 
     * @param string $name the cookie name
     * @param bool $inBrowser check cookie awailable only in browser.
     * @return bool whether the named cookie exists
     * @see remove()
     */
    public function has(string $name, bool $inBrowser = true): bool
    {
        $cookie = $this->get($name);
        if (!$cookie instanceof Cookie) {
            return false;
        }

        if ($inBrowser) {
            return $cookie->getValue() !== '' && !$cookie->isExpired();
        }

        return true;
    }

    /**
     * Adds a cookie to the collection.
     * 
     * If there is already a cookie with the same name in the collection, it will be removed first.
     * 
     * If a cookie with the same name added before with attribute `$readonly = true`,
     * then adding cookie with same name again will do nothing.
     * 
     * @param Cookie $cookie the cookie object to be added
     * @param bool $guarded Cookie can not be deleted, even if you add cookie with the same name later.
     * It usable only for response cookies to prevent accidentally delete some important cookies.
     * Use carefully!!!
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function add(Cookie $cookie, bool $guarded = false): self
    {
        $this->assertNotReadonly();

        if ($this->isGuarded($cookie)) {
            return $this;
        }

        if ($guarded) {
            $this->setGuarded($cookie);
        }

        $this->offsetSet($cookie->getName(), $cookie);

        return $this;
    }

    /**
     * Removes a cookie object.
     * If `$removeFromBrowser` is true, the cookie will be removed from the browser.
     * In this case, a cookie with outdated expiry will be added to the collection.
     * 
     * If cookie was added as readonly, method will do nothing
     * 
     * @param Cookie $cookie the cookie object of the cookie to be removed.
     * @param bool $removeFromBrowser whether to remove the cookie from browser
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function remove(Cookie $cookie, bool $removeFromBrowser = true): self
    {
        $this->assertNotReadonly();
        if ($this->isGuarded($cookie)) {
            return $this;
        }

        $cookie->setValue('')->setExpiryTime(1)->setPath('/');
        if ($removeFromBrowser) {
            $this->add($cookie, true);
        } else {
            $this->offsetUnset($cookie->getName());
        }

        return $this;
    }

    /**
     * Removes a cookie by his name.
     * If `$removeFromBrowser` is true, the cookie will be removed from the browser.
     * In this case, a cookie with outdated expiry will be added to the collection.
     * @param string $name the cookie name of the cookie to be removed.
     * @param bool $removeFromBrowser whether to remove the cookie from browser
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function removeByName(string $name, bool $removeFromBrowser = true): self
    {
        $this->assertNotReadonly();
        $cookie = (new Cookie($name))->setExpiryTime(1)->setPath('/');
        if ($this->isGuarded($cookie)) {
            return $this;
        }
        if ($removeFromBrowser) {
            $this->add($cookie, true);
        } else {
            $this->offsetUnset($cookie->getName());
        }

        return $this;
    }

    /**
     * Removes all cookies.
     * @throws InvalidCallException if the cookie collection is read only
     */
    public function removeAll(): self
    {
        $this->assertNotReadonly();

        /** @var Cookie $cookie */
        foreach ($this->storage as $cookie) {
            $this->remove($cookie);
            $this->remove($cookie, false);
        }

        return $this;
    }

    /**
     * Populates the cookie collection from an array.
     * 
     * @param array<string, Cookie> $array the cookies to populate from
     */
    public function fromArray(array $array): void
    {
        $this->assertIsCookies($array);
        $this->storage = $array;
    }

    /**
     * Assert an array is array of Cookie objects.
     * 
     * @param array $array Array to assert.
     * 
     * @return void
     */
    protected function assertIsCookies(array $array): void
    {
        Assert::allIsInstanceOf($array, Cookie::class);
    }

    /**
     * Assert this collection is not readonly.
     * 
     * @return void
     */
    protected function assertNotReadonly(): void
    {
        if ($this->readOnly) {
            throw new InvalidCallException('The cookie collection is read only.');
        }
    }

    /**
     * Check that cookie is guarded?
     * 
     * @param Cookie $cookie
     * 
     * @return bool
     */
    protected function isGuarded(Cookie $cookie): bool
    {
        return in_array($cookie->getName(), $this->guarded);
    }

    /**
     * Set cookie as guarded.
     * 
     * @param Cookie $cookie
     * 
     * @return void
     */
    protected function setGuarded(Cookie $cookie): void
    {
        $this->guarded[] = $cookie->getName();
    }
}