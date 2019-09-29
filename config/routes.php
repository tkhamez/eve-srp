<?php

declare(strict_types=1);

use Slim\App;

return function (App $app): void
{
    // auth
    $app->get('/login',  Brave\EveSrp\Controller\AuthController::class . ':login');
    $app->get('/auth',   Brave\EveSrp\Controller\AuthController::class . ':auth');
    $app->get('/logout', Brave\EveSrp\Controller\AuthController::class . ':logout');

    $app->get('/',             Brave\EveSrp\Controller\HomeController::class);
    $app->get('/request/{id}', Brave\EveSrp\Controller\RequestController::class . ':show');
    $app->get('/submit',       Brave\EveSrp\Controller\SubmitController::class);
    $app->get('/review',       Brave\EveSrp\Controller\ReviewController::class);
    $app->get('/pay',          Brave\EveSrp\Controller\PayController::class);

    $app->get ('/admin/divisions',        Brave\EveSrp\Controller\AdminController::class . ':divisions');
    $app->post('/admin/divisions/new',    Brave\EveSrp\Controller\AdminController::class . ':newDivision');
    $app->post('/admin/divisions/delete', Brave\EveSrp\Controller\AdminController::class . ':deleteDivision');
    $app->get ('/admin/groups',           Brave\EveSrp\Controller\AdminController::class . ':groups');
    $app->post('/admin/groups/sync',      Brave\EveSrp\Controller\AdminController::class . ':syncGroups');
    $app->get ('/admin/permissions',      Brave\EveSrp\Controller\AdminController::class . ':permissions');
    $app->post('/admin/permissions/save', Brave\EveSrp\Controller\AdminController::class . ':savePermissions');
};
