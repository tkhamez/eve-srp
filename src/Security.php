<?php

declare(strict_types=1);

namespace Brave\EveSrp;

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
     * May submit a request
     */
    public const ROLE_SUBMIT = 'submit';

    /**
     * May approve a request
     */
    public const ROLE_APPROVE = 'approve';

    /**
     * May payout the ISK.
     */
    public const ROLE_PAY = 'pay';
}
