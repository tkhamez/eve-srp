<?php

return [
    // SSO CONFIGURATION
    'SSO_CLIENT_ID' => getenv('APP_SSO_CLIENT_ID'),
    'SSO_CLIENT_SECRET' => getenv('APP_SSO_CLIENT_SECRET'),
    'SSO_REDIRECTURI' => getenv('APP_SSO_REDIRECTURI'),
    'SSO_URL_AUTHORIZE' => 'https://login.eveonline.com/v2/oauth/authorize',
    'SSO_URL_ACCESSTOKEN' => 'https://login.eveonline.com/v2/oauth/token',
    'SSO_URL_RESOURCEOWNERDETAILS' => '', // only for SSO v1
    'SSO_URL_JWT_KEY_SET' => 'https://login.eveonline.com/oauth/jwks',
    'SSO_SCOPES' => '',

    // App
    'APP_ENV' => getenv('APP_ENV'),
    'brave.serviceName' => 'Brave Collective - SRP',

    // NEUCORE
    'CORE_URL' => getenv('APP_CORE_URL'),
    'CORE_APP_ID' => getenv('APP_CORE_CLIENT_ID'),
    'CORE_APP_TOKEN' => getenv('APP_CORE_CLIENT_TOKEN'),

    // DB
    'DB_URL' => getenv('APP_DB_URL')
];
