<?php

declare(strict_types=1);

namespace core\auth;

use core\interfaces\IdentityInterface;
use core\exception\InvalidConfigException;
use core\web\Cookie;
use core\validators\Assert;

/**
 * Authorization agent by cookie.
 */
class AuthAgentByCookie implements AuthAgentInterface
{
    /**
     * @var int Authorization cookie life time duration
     */
    private int $duration = 0;

    /**
     * @var IdentityInterface
     */
    private IdentityInterface $identity;

    /**
     * Constructor.
     * 
     * @param Cookie $cookie Cookie contains authorization data.
     * @param string $identityClass Class name implements `IdentityInterface` used to authorization.
     * @throws InvalidConfigException If identity class invalid
     * @throws InvalidConfigException If decoded cookie data has invalid format
     * @throws InvalidArgumentException If cookie value has no json format
     */
    public function __construct(private Cookie $cookie, private string $identityClass)
    {
        Assert::classExists($identityClass);
        Assert::implementsInterface($identityClass, IdentityInterface::class);
    }

    /**
     * Get instance from user identity.
     * Usable if you need to generate auth cookie only (i.e. after successfull login)
     * 
     * @param IdentityInterface $identity
     * @param string $cookieName
     * 
     * @return self
     */
    public static function fromIdentity(IdentityInterface $identity, string $cookieName): self
    {
        $cookie = new Cookie($cookieName);
        $new = new self($cookie, get_class($identity));
        $new->setIdentity($identity);
        return $new;
    }

    /**
     * Create authorization cookie.
     * 
     * @return Cookie Authorization cookie.
     * @throws InvalidConfigException if no identity set.
     */
    public function getAuthCookie(): Cookie
    {
        if (!isset($this->identity)) {
            throw new InvalidConfigException('Cannot get authorization cookie without identity.');
        }

        $data = [$this->identity->getId(), $this->identity->getAuthKey(), $this->duration];
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $cookie = new Cookie($this->cookie->getName(), $json);
        if ($this->duration === 0) {
            $cookie->setExpiryTime(0);
        } else {
            $cookie->setMaxAge($this->duration);
        }

        return $cookie;
    }

    /**
     * Set the value of authorization cookie life time duration
     * 
     * @param int $duration Time in seconds
     * 
     * @return self
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Get the value of duration
     * 
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param IdentityInterface $identity
     * 
     * @return self
     */
    public function setIdentity(IdentityInterface $identity): self
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIdentity(): ?IdentityInterface
    {
        if (!isset($this->identity)) {
            $value = $this->cookie->getValue();
            Assert::json($value);
            $data = json_decode($value, true);
            if (!is_array($data) || count($data) !== 3) {
                throw new InvalidConfigException('Invalid authorization cookie format');
            }
            list($id, $authKey, $duration) = $data;

            /** @var IdentityInterface $class  */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
            if ($identity === null) {
                return null;
            }

            if (!$identity->validateAuthKey($authKey)) {
                return null;
            }

            $this->setDuration($duration);
            $this->setIdentity($identity);
        }

        return $this->identity;
    }
}