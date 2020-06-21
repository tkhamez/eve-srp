<?php

declare(strict_types=1);

use Slim\App;

return function (App $app): void
{
    $app->get('/login',  EveSrp\Controller\AuthController::class . ':login');
    $app->get('/auth',   EveSrp\Controller\AuthController::class . ':auth');
    $app->get('/logout', EveSrp\Controller\AuthController::class . ':logout');
    $app->get('/ping',   EveSrp\Controller\PingController::class);

    $app->get('/',                     EveSrp\Controller\MyRequestsController::class);
    $app->get('/my-requests',          EveSrp\Controller\MyRequestsController::class);
    $app->get('/request/{id}/show',    EveSrp\Controller\RequestController::class . ':show');
    $app->get('/request/{id}/process', EveSrp\Controller\RequestController::class . ':process');
    $app->get('/submit',               EveSrp\Controller\SubmitController::class . ':showForm');
    $app->post('/submit',              EveSrp\Controller\SubmitController::class . ':submitForm');
    $app->get('/review',               EveSrp\Controller\ProcessListsController::class . ':review');
    $app->get('/pay',                  EveSrp\Controller\ProcessListsController::class . ':pay');
    $app->get('/all-requests',         EveSrp\Controller\AllRequestsController::class);

    $app->get ('/admin/divisions',        EveSrp\Controller\AdminController::class . ':divisions');
    $app->post('/admin/divisions/new',    EveSrp\Controller\AdminController::class . ':newDivision');
    $app->post('/admin/divisions/delete', EveSrp\Controller\AdminController::class . ':deleteDivision');
    $app->get ('/admin/groups',           EveSrp\Controller\AdminController::class . ':groups');
    $app->post('/admin/groups/sync',      EveSrp\Controller\AdminController::class . ':syncGroups');
    $app->get ('/admin/permissions',      EveSrp\Controller\AdminController::class . ':permissions');
    $app->post('/admin/permissions/save', EveSrp\Controller\AdminController::class . ':savePermissions');
};
