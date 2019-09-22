<?php

use Psr\Container\ContainerInterface;
use Slim\App;

return function (ContainerInterface $container)
{
    /** @var App $app */
    $app = $container->get(App::class);

    // auth
    $app->get('/login',  Brave\EveSrp\Controller\Authentication::class . ':index');
    $app->get('/auth',   Brave\EveSrp\Controller\Authentication::class . ':auth');
    $app->get('/logout', Brave\EveSrp\Controller\Authentication::class . ':logout');

    $app->get('/', Brave\EveSrp\Controller\Home::class);

    return $app;
};
