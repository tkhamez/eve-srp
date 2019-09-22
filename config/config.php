<?php

return [
    // SSO CONFIGURATION
    'SSO_CLIENT_ID' => getenv('SSO_CLIENT_ID'),
    'SSO_CLIENT_SECRET' => getenv('SSO_CLIENT_SECRET'),
    'SSO_REDIRECTURI' => getenv('SSO_REDIRECTURI'),
    'SSO_URL_AUTHORIZE' => 'https://login.eveonline.com/v2/oauth/authorize',
    'SSO_URL_ACCESSTOKEN' => 'https://login.eveonline.com/v2/oauth/token',
    'SSO_URL_RESOURCEOWNERDETAILS' => '', // only for SSO v1
    'SSO_URL_JWT_KEY_SET' => 'https://login.eveonline.com/oauth/jwks',
    'SSO_SCOPES' => '',

    // App
    'brave.serviceName' => getenv('BRAVE_SERVICENAME'),

    // NEUCORE
    'CORE_URL' => getenv('CORE_URL'),
    'CORE_APP_ID' => getenv('CORE_APP_ID'),
    'CORE_APP_TOKEN' => getenv('CORE_APP_TOKEN'),

    // DB
    'DB_URL' => getenv('DB_URL')
];
