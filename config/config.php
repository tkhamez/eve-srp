<?php

declare(strict_types=1);

return [
    'APP_ENV'         => $_ENV['EVE_SRP_ENV'] ?? 'prod',
    'DB_URL'          => $_ENV['EVE_SRP_DB_URL'],

    // Customizing
    'APP_TITLE'          => $_ENV['EVE_SRP_APP_TITLE'] ?? 'Ship Replacement Program',
    'APP_LOGO'           => $_ENV['EVE_SRP_APP_LOGO'] ?? '/static/logo-srp.png',
    'APP_LOGO_ALT'       => $_ENV['EVE_SRP_APP_LOGO_ALT'] ?? 'SRP Logo',
    'LOGIN_HINT'         => $_ENV['EVE_SRP_LOGIN_HINT'] ?? '',
    'FOOTER_TEXT'        => $_ENV['EVE_SRP_FOOTER_TEXT'] ?? '',

    // Admin role, group and character providers
    'ROLE_GLOBAL_ADMIN' => $_ENV['EVE_SRP_ROLE_GLOBAL_ADMIN'],
    'PROVIDER'          => $_ENV['EVE_SRP_PROVIDER'],

    // SSO configuration
    'SSO_CLIENT_ID'        => $_ENV['EVE_SRP_SSO_CLIENT_ID'],
    'SSO_CLIENT_SECRET'    => $_ENV['EVE_SRP_SSO_CLIENT_SECRET'],
    'SSO_REDIRECT_URI'     => $_ENV['EVE_SRP_SSO_REDIRECT_URI'],
    'SSO_URL_AUTHORIZE'    => 'https://login.eveonline.com/v2/oauth/authorize',
    'SSO_URL_ACCESS_TOKEN' => 'https://login.eveonline.com/v2/oauth/token',
    'SSO_URL_JWT_KEY_SET'  => 'https://login.eveonline.com/oauth/jwks',

    // Neucore
    'NEUCORE_DOMAIN'    => $_ENV['EVE_SRP_NEUCORE_DOMAIN'] ?? '',
    'NEUCORE_APP_ID'    => $_ENV['EVE_SRP_NEUCORE_APP_ID'] ?? '',
    'NEUCORE_APP_TOKEN' => $_ENV['EVE_SRP_NEUCORE_APP_TOKEN'] ?? '',

    // other stuff
    'HTTP_USER_AGENT'     => $_ENV['EVE_SRP_HTTP_USER_AGENT'] ?? 'EVE-SRP (https://github.com/tkhamez/eve-srp)',
    'ESI_BASE_URL'        => 'https://esi.evetech.net/',
    'ZKILLBOARD_BASE_URL' => rtrim($_ENV['EVE_SRP_ZKILLBOARD_URL'] ?? 'https://zkillboard.com', '/') . '/',
];
