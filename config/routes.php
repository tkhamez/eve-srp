<?php

declare(strict_types=1);

use Slim\App;

return function (App $app): void
{
    // auth
    $app->get('/login',  Brave\EveSrp\Controller\Authentication::class . ':index');
    $app->get('/auth',   Brave\EveSrp\Controller\Authentication::class . ':auth');
    $app->get('/logout', Brave\EveSrp\Controller\Authentication::class . ':logout');

    $app->get('/', Brave\EveSrp\Controller\Home::class);
    $app->get('/submit', Brave\EveSrp\Controller\Submit::class);
    $app->get('/approve', Brave\EveSrp\Controller\Approve::class);
    $app->get('/pay', Brave\EveSrp\Controller\Pay::class);
    $app->get('/request/{id}', Brave\EveSrp\Controller\Request::class . ':show');
};
