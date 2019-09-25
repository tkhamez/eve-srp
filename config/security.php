<?php

declare(strict_types=1);

use Brave\EveSrp\Security;

/**
 * Required roles (one of them) for routes.
 *
 * First route match will be used, matched by "starts-with"
 */
return [
    '/login'   => [Security::ROLE_ANY],
    '/auth'    => [Security::ROLE_ANY],
    '/submit'  => [Security::ROLE_SUBMIT],
    '/approve' => [Security::ROLE_APPROVE],
    '/pay'     => [Security::ROLE_PAY],
    '/request' => [Security::ROLE_SUBMIT, Security::ROLE_APPROVE, Security::ROLE_PAY],
    '/'        => [Security::ROLE_AUTHENTICATED],
];
