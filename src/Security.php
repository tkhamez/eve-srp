<?php

declare(strict_types=1);

namespace EveSrp;

class Security
{
    /**
     * This role is always added.
     */
    public const ROLE_ANY = 'any';

    /**
     * Added to all authenticated clients
     */
    public const ROLE_AUTHENTICATED = 'authenticated';

    /**
     * Can create divisions etc.
     */
    public const GLOBAL_ADMIN = 'global-admin';
}
