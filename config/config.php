<?php

declare(strict_types=1);

use GuzzleHttp\Client;

return [
    // App
    'APP_ENV'           => getenv('EVE_SRP_ENV'),
    'brave.serviceName' => getenv('EVE_SRP_APP_TITLE'),
    'FOOTER_TEXT'       => getenv('EVE_SRP_FOOTER_TEXT'),
    'HTTP' => [
        'user_agent' => 'EVE-SRP/1.0 (https://github.com/bravecollective/eve-srp) GuzzleHttp/' . Client::VERSION,
    ],

    // SSO configuration
    'SSO_CLIENT_ID'        => getenv('EVE_SRP_SSO_CLIENT_ID'),
    'SSO_CLIENT_SECRET'    => getenv('EVE_SRP_SSO_CLIENT_SECRET'),
    'SSO_REDIRECT_URI'     => getenv('EVE_SRP_SSO_REDIRECT_URI'),
    'SSO_URL_AUTHORIZE'    => 'https://login.eveonline.com/v2/oauth/authorize',
    'SSO_URL_ACCESS_TOKEN' => 'https://login.eveonline.com/v2/oauth/token',
    'SSO_URL_JWT_KEY_SET'  => 'https://login.eveonline.com/oauth/jwks',

    // Neucore
    'CORE_URL'       => getenv('EVE_SRP_CORE_URL'),
    'CORE_APP_ID'    => getenv('EVE_SRP_CORE_APP_ID'),
    'CORE_APP_TOKEN' => getenv('EVE_SRP_CORE_APP_TOKEN'),

    // provider
    'GROUP_PROVIDER'     => getenv('EVE_SRP_GROUP_PROVIDER'),
    'CHARACTER_PROVIDER' => getenv('EVE_SRP_CHARACTER_PROVIDER'),
    
    // global admin role mapping
    'ROLE_GLOBAL_ADMIN' => getenv('EVE_SRP_ROLE_GLOBAL_ADMIN'),
    
    // Database
    'DB_URL' => getenv('EVE_SRP_DB_URL'),
];
