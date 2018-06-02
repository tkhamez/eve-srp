<?php

use Brave\Sso\Basics\AuthenticationController;

return function (\Psr\Container\ContainerInterface $container)
{
    /** @var \Slim\App $app */
    $app = $container[\Slim\App::class];

    // SSO via sso-basics package
    $app->get('/login', AuthenticationController::class . ':index');
    $app->post('/auth', AuthenticationController::class . ':auth');

    return $app;
};
