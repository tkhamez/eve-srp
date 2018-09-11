<?php

return [
    'settings' => require_once('config.php'),

    \Slim\App::class => function (\Psr\Container\ContainerInterface $container)
    {
        return new Slim\App($container);
    },

    \League\OAuth2\Client\Provider\GenericProvider::class => function (\Psr\Container\ContainerInterface $container)
    {
        $settings = $container->get('settings');

        return new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $settings['SSO_CLIENT_ID'],
            'clientSecret' => $settings['SSO_CLIENT_SECRET'],
            'redirectUri' => $settings['SSO_REDIRECTURI'],
            'urlAuthorize' => $settings['SSO_URL_AUTHORIZE'],
            'urlAccessToken' => $settings['SSO_URL_ACCESSTOKEN'],
            'urlResourceOwnerDetails' => $settings['SSO_URL_RESOURCEOWNERDETAILS'],
        ]);
    },

    \Brave\Sso\Basics\AuthenticationProvider::class => function (\Psr\Container\ContainerInterface $container)
    {
        $settings = $container->get('settings');

        return new \Brave\Sso\Basics\AuthenticationProvider(
            $container->get(\League\OAuth2\Client\Provider\GenericProvider::class),
            explode(' ', $settings['SSO_SCOPES'])
        );
    },

    \Brave\CoreConnector\SessionHandler::class => function (\Psr\Container\ContainerInterface $container) {
        return new \Brave\CoreConnector\SessionHandler($container);
    },

    \Brave\Sso\Basics\SessionHandlerInterface::class => function (\Psr\Container\ContainerInterface $container) {
        return $container->get(\Brave\CoreConnector\SessionHandler::class);
    },

    \Brave\NeucoreApi\Api\ApplicationApi::class => function (\Psr\Container\ContainerInterface $container) {
        $apiKey = base64_encode(
            $container->get('settings')['CORE_APP_ID'] .
            ':'.
            $container->get('settings')['CORE_APP_TOKEN']
        );
        $config = Brave\NeucoreApi\Configuration::getDefaultConfiguration();
        $config->setHost($container->get('settings')['CORE_URL']);
        $config->setApiKey('Authorization', $apiKey);
        $config->setApiKeyPrefix('Authorization', 'Bearer');

        return new Brave\NeucoreApi\Api\ApplicationApi(null, $config);
    },

    \Brave\CoreConnector\RoleProvider::class => function (\Psr\Container\ContainerInterface $container) {
        return new \Brave\CoreConnector\RoleProvider(
            $container->get(\Brave\NeucoreApi\Api\ApplicationApi::class),
            $container->get(\Brave\Sso\Basics\SessionHandlerInterface::class)
        );
    },
];
