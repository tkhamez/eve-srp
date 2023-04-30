<?php

declare(strict_types=1);

use EveSrp\Model\Permission;
use EveSrp\Security;

/**
 * Required roles (one of them) for routes.
 *
 * First route match will be used, matched by "starts-with"
 */
return [
    '/login'    => [Security::ROLE_ANY],
    '/auth'     => [Security::ROLE_ANY],

    '/submit'       => [Permission::SUBMIT],
    '/open'         => [Permission::REVIEW],
    '/in-progress'  => [Permission::REVIEW],
    '/approved'     => [Permission::PAY],
    '/all-requests' => [Permission::REVIEW, Permission::PAY, Permission::ADMIN, Security::GLOBAL_ADMIN],

    '/request/' => [Permission::SUBMIT, Permission::REVIEW, Permission::PAY],

    '/admin/divisions' => [Security::GLOBAL_ADMIN],
    '/admin/groups'    => [Security::GLOBAL_ADMIN],
    '/admin'           => [Security::GLOBAL_ADMIN, Permission::ADMIN],
    
    '/' => [Security::ROLE_AUTHENTICATED],
];
