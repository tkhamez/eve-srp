<?php

declare(strict_types=1);

use Slim\App;

return function (App $app): void
{
    // auth
    $app->get('/login',  Brave\EveSrp\Controller\AuthController::class . ':index');
    $app->get('/auth',   Brave\EveSrp\Controller\AuthController::class . ':auth');
    $app->get('/logout', Brave\EveSrp\Controller\AuthController::class . ':logout');

    $app->get('/',             Brave\EveSrp\Controller\HomeController::class);
    $app->get('/request/{id}', Brave\EveSrp\Controller\RequestController::class . ':show');
    $app->get('/submit',       Brave\EveSrp\Controller\SubmitController::class);
    $app->get('/review',       Brave\EveSrp\Controller\ReviewController::class);
    $app->get('/pay',          Brave\EveSrp\Controller\PayController::class);
    $app->get('/admin',        Brave\EveSrp\Controller\AdminController::class);
};
