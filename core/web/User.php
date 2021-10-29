<?php

declare(strict_types=1);

namespace core\web;

use core\traits\GetSetByPropsTrait;
use core\interfaces\IdentityInterface;

/**
 * Implementation of site user
 * 
 * @property IdentityInterface|null $identity Site user identity object.
 * @property bool $isGuest Current site user is guest or not
 * @property bool $isAuth Current site user is authenticated or not
 */
class User
{
    use GetSetByPropsTrait;

    /**
     * @var IdentityInterface|null $identity the identity object holder.
     */
    private ?IdentityInterface $identity = null;

    /**
     * Logs in a user.
     *
     * After logging in a user the user's identity information is obtainable from the '$identity' property.
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity): bool
    {
        $this->identity = $identity;
        return $this->identity !== null;
    }

    /**
     * Logs out the current user.
     * 
     * @return bool whether the user is logged out
     */
    public function logout(): bool
    {
        $this->identity = null;
        return $this->identity === null;
    }

    /**
     * Returns the identity object associated with the currently logged-in user.
     * 
     * @return IdentityInterface|null the identity object associated with the currently logged-in user.
     * `null` is returned if the user is not logged in (not authenticated).
     */
    public function getIdentityAttribute(): ?IdentityInterface
    {
        return $this->identity;
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * 
     * @return bool whether the current user is a guest.
     */
    public function getIsGuestAttribute(): bool
    {
        return $this->identity === null;
    }

    /**
     * Returns a value indicating whether the user is authenticated.
     * 
     * @return bool whether the current user is authenticated.
     */
    public function getIsAuthAttribute(): bool
    {
        return $this->identity instanceof IdentityInterface;
    }
}