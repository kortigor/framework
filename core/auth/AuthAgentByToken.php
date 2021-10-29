<?php

declare(strict_types=1);

namespace core\auth;

use core\interfaces\IdentityInterface;
use core\exception\InvalidConfigException;
use core\validators\Assert;

/**
 * Authorization agent by access token.
 */
class AuthAgentByToken implements AuthAgentInterface
{
    /**
     * @var IdentityInterface
     */
    private IdentityInterface $identity;

    /**
     * Constructor.
     * 
     * @param string $token Authorization token.
     * @param string $identityClass Class name implements `IdentityInterface` used to authorization
     * 
     * @throws InvalidConfigException If identity class not exists or not implements `IdentityInterface`
     */
    public function __construct(private string $token, private string $identityClass)
    {
        Assert::classExists($identityClass);
        Assert::implementsInterface($identityClass, IdentityInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentity(): ?IdentityInterface
    {
        if (!isset($this->identity)) {
            /** @var IdentityInterface $class  */
            $class = $this->identityClass;
            if (!$class::validateToken($this->token)) {
                return null;
            }

            $identity = $class::findIdentityByAccessToken($this->token);
            if ($identity === null) {
                return null;
            }

            $this->identity = $identity;
        }

        return $this->identity;
    }
}
