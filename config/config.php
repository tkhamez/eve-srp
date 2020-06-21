<?php

declare(strict_types=1);

return [
    'APP_ENV'         => getenv('EVE_SRP_ENV'),
    'DB_URL'          => getenv('EVE_SRP_DB_URL'),

    // Customizing
    'APP_TITLE'          => getenv('EVE_SRP_APP_TITLE'),
    'APP_LOGO'           => getenv('EVE_SRP_APP_LOGO'),
    'APP_LOGO_ALT'       => getenv('EVE_SRP_APP_LOGO_ALT'),
    'LOGIN_HINT'         => getenv('EVE_SRP_LOGIN_HINT'),
    'FOOTER_TEXT'        => getenv('EVE_SRP_FOOTER_TEXT'),

    // Admin role, group and character providers
    'ROLE_GLOBAL_ADMIN'  => getenv('EVE_SRP_ROLE_GLOBAL_ADMIN'),
    'GROUP_PROVIDER'     => getenv('EVE_SRP_GROUP_PROVIDER'),
    'CHARACTER_PROVIDER' => getenv('EVE_SRP_CHARACTER_PROVIDER'),

    // SSO configuration
    'SSO_CLIENT_ID'        => getenv('EVE_SRP_SSO_CLIENT_ID'),
    'SSO_CLIENT_SECRET'    => getenv('EVE_SRP_SSO_CLIENT_SECRET'),
    'SSO_REDIRECT_URI'     => getenv('EVE_SRP_SSO_REDIRECT_URI'),
    'SSO_URL_AUTHORIZE'    => 'https://login.eveonline.com/v2/oauth/authorize',
    'SSO_URL_ACCESS_TOKEN' => 'https://login.eveonline.com/v2/oauth/token',
    'SSO_URL_JWT_KEY_SET'  => 'https://login.eveonline.com/oauth/jwks',

    // Neucore
    'NEUCORE_DOMAIN'    => getenv('EVE_SRP_NEUCORE_DOMAIN'),
    'NEUCORE_APP_ID'    => getenv('EVE_SRP_NEUCORE_APP_ID'),
    'NEUCORE_APP_TOKEN' => getenv('EVE_SRP_NEUCORE_APP_TOKEN'),

    // other stuff
    'HTTP_USER_AGENT'    => getenv('EVE_SRP_HTTP_USER_AGENT'),
    'ESI_BASE_URL'       => 'https://esi.evetech.net/',
    'ZKILLBOARD_BASE_URL' => rtrim(getenv('EVE_SRP_ZKILLBOARD_URL'), '/') . '/',
];
