<?php

use Brave\EveSrp\RoleProvider;

/**
 * Required roles (one of them) for routes.
 *
 * First route match will be used, matched by "starts-with"
 */
return [
    '/login' => [RoleProvider::ROLE_ANY],
    '/auth'  => [RoleProvider::ROLE_ANY],
    '/'      => [RoleProvider::ROLE_AUTHENTICATED],
];
