<?php

return [
    // Slim
    'displayErrorDetails' => false,
    'determineRouteBeforeAppMiddleware' => true,

    // SSO CONFIGURATION
    'SSO_CLIENT_ID' => '',
    'SSO_CLIENT_SECRET' => '',
    'SSO_REDIRECTURI' => '',
    'SSO_URL_AUTHORIZE' => 'https://login.eveonline.com/oauth/authorize', // for SSO v2: .../v2/oauth/authorize
    'SSO_URL_ACCESSTOKEN' => 'https://login.eveonline.com/oauth/token', // for SSO v2: .../v2/oauth/token
    'SSO_URL_RESOURCEOWNERDETAILS' => 'https://esi.evetech.net/verify', // for SSO v1
    'SSO_URL_JWT_KEY_SET' => 'https://login.eveonline.com/oauth/jwks', // for SSO v2
    'SSO_SCOPES' => '',

    // App
    'brave.serviceName' => 'Add your service name here',

    // NEUCORE
    'CORE_URL' => 'https://account.bravecollective.com/api',
    'CORE_APP_ID' => '',
    'CORE_APP_TOKEN' => '',

    // DB
    'DB_URL' => ''
];
