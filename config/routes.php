<?php

return function (\Psr\Container\ContainerInterface $container)
{
    /** @var \Slim\App $app */
    $app = $container[\Slim\App::class];

    $app->get('/', 'Brave\CoreConnector\HomeController');

    // SSO via sso-basics package
    $app->get('/login', 'Brave\Sso\Basics\AuthenticationController:index');
    $app->get('/auth', 'Brave\CoreConnector\AuthenticationController:auth');

    return $app;
};
