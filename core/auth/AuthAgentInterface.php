<?php

declare(strict_types=1);

namespace core\auth;

use core\interfaces\IdentityInterface;

interface AuthAgentInterface
{
    /**
     * Get user identity.
     * 
     * @return IdentityInterface|null Identity object on authorization success or null if fails.
     */
    public function getIdentity(): ?IdentityInterface;
}
