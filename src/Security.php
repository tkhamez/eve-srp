<?php

declare(strict_types=1);

namespace Brave\EveSrp;

class Security
{
    /**
     * This role is always added.
     */
    public const ROLE_ANY = 'role:any';

    /**
     * Added to all authenticated clients
     */
    public const ROLE_AUTHENTICATED = 'role:authenticated';

    /**
     * May submit a request
     */
    public const ROLE_REQUEST = 'role:request';

    /**
     * May approve a request
     */
    public const ROLE_APPROVE = 'role:approve';

    /**
     * May payout the ISK.
     */
    public const ROLE_PAY = 'role:pay';
}
