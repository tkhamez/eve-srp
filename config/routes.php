<?php

use Psr\Container\ContainerInterface;
use Slim\App;

return function (ContainerInterface $container)
{
    /** @var App $app */
    $app = $container->get(App::class);

    $app->get('/', 'Brave\CoreConnector\HomeController');
    $app->get('/secured', function () {
        echo 'secured';
    });

    // SSO via sso-basics package
    $app->get('/login', 'Brave\Sso\Basics\AuthenticationController:index');
    $app->get('/auth', 'Brave\CoreConnector\AuthenticationController:auth');
    $app->get('/logout', 'Brave\CoreConnector\AuthenticationController:logout');

    return $app;
};
