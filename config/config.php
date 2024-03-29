<?php

declare(strict_types=1);

use EveSrp\Controller\RequestController;

return [
    // General
    'APP_ENV'        => $_ENV['EVE_SRP_ENV'] ?? 'prod',
    'DB_URL'         => $_ENV['EVE_SRP_DB_URL'],
    'SESSION_SECURE' => $_ENV['EVE_SRP_SESSION_SECURE'] ?? '0',

    // Customizing
    'APP_TITLE'                  => $_ENV['EVE_SRP_APP_TITLE']    ?? 'Ship Replacement Program',
    'APP_LOGO'                   => $_ENV['EVE_SRP_APP_LOGO']     ?? '/static/logo-srp.png',
    'APP_LOGO_ALT'               => $_ENV['EVE_SRP_APP_LOGO_ALT'] ?? 'SRP Logo',
    'LOGIN_HINT'                 => $_ENV['EVE_SRP_LOGIN_HINT']   ?? '',
    'FOOTER_TEXT'                => $_ENV['EVE_SRP_FOOTER_TEXT']  ?? '',
    'SUBMIT_DETAILS_PLACEHOLDER' => $_ENV['EVE_SRP_SUBMIT_DETAILS_PLACEHOLDER'] ?? '',
    'SUBMIT_DETAILS_HELP'        => $_ENV['EVE_SRP_SUBMIT_DETAILS_HELP'] ?? '',
    'MODIFIER_CALCULATION'       => $_ENV['EVE_SRP_MODIFIER_CALCULATION'] ?? RequestController::MODIFIER_SEQUENTIALLY,

    // Global admin role, group and character provider
    'ROLE_GLOBAL_ADMIN' => $_ENV['EVE_SRP_ROLE_GLOBAL_ADMIN'] ?? 'global-admin',
    'PROVIDER'          => $_ENV['EVE_SRP_PROVIDER'] ?? 'EveSrp\Provider\Implementation\EsiProvider',

    'SSO_CLIENT_ID'        => $_ENV['EVE_SRP_SSO_CLIENT_ID'],
    'SSO_CLIENT_SECRET'    => $_ENV['EVE_SRP_SSO_CLIENT_SECRET'],
    'SSO_REDIRECT_URI'     => $_ENV['EVE_SRP_SSO_REDIRECT_URI'],

    'NEUCORE_DOMAIN'     => $_ENV['EVE_SRP_PROVIDER_NEUCORE_DOMAIN'] ?? '',
    'NEUCORE_APP_ID'     => $_ENV['EVE_SRP_PROVIDER_NEUCORE_APP_ID'] ?? '',
    'NEUCORE_APP_SECRET' => $_ENV['EVE_SRP_PROVIDER_NEUCORE_APP_SECRET'] ?? '',

    // Other
    'HTTP_USER_AGENT' => $_ENV['EVE_SRP_HTTP_USER_AGENT'] ?? 'EVE-SRP (https://github.com/tkhamez/eve-srp)',

    'URLs' => [
        'zkillboard' => $_ENV['EVE_SRP_ZKILLBOARD_URL'] ?? 'https://zkillboard.com',
        'esi'        => 'https://esi.evetech.net',
        'dotlan'     => 'https://evemaps.dotlan.net',
        'nakamura'   => 'https://time.nakamura-labs.com',
        'evewho'     => 'https://evewho.com',
    ]
];
