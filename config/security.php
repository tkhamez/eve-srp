<?php

declare(strict_types=1);

use Brave\EveSrp\Security;

/**
 * Required roles (one of them) for routes.
 *
 * First route match will be used, matched by "starts-with"
 */
return [
    '/login' => [Security::ROLE_ANY],
    '/auth'  => [Security::ROLE_ANY],
    '/'      => [Security::ROLE_AUTHENTICATED],
];
