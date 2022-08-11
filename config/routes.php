<?php

declare(strict_types=1);

return [
    ['get', '/login',  [EveSrp\Controller\AuthController::class, 'login']],
    ['get', '/auth',   [EveSrp\Controller\AuthController::class, 'auth']],
    ['get', '/logout', [EveSrp\Controller\AuthController::class, 'logout']],
    ['get', '/ping',   EveSrp\Controller\PingController::class],

    ['get', '/',                     EveSrp\Controller\MyRequestsController::class],
    ['get', '/my-requests',          EveSrp\Controller\MyRequestsController::class],
    ['get', '/request/{id}/show',    [EveSrp\Controller\RequestController::class, 'show']],
    ['get', '/request/{id}/process', [EveSrp\Controller\RequestController::class, 'process']],
    ['get', '/submit',               [EveSrp\Controller\SubmitController::class, 'showForm']],
    ['post', '/submit',              [EveSrp\Controller\SubmitController::class, 'submitForm']],
    ['get', '/review',               [EveSrp\Controller\ProcessListsController::class, 'review']],
    ['get', '/pay',                  [EveSrp\Controller\ProcessListsController::class, 'pay']],
    ['get', '/all-requests',         EveSrp\Controller\AllRequestsController::class],

    ['get',  '/admin/divisions',        [EveSrp\Controller\AdminController::class, 'divisions']],
    ['post', '/admin/divisions/new',    [EveSrp\Controller\AdminController::class, 'newDivision']],
    ['post', '/admin/divisions/delete', [EveSrp\Controller\AdminController::class, 'deleteDivision']],
    ['get',  '/admin/groups',           [EveSrp\Controller\AdminController::class, 'groups']],
    ['post', '/admin/groups/sync',      [EveSrp\Controller\AdminController::class, 'syncGroups']],
    ['get',  '/admin/permissions',      [EveSrp\Controller\AdminController::class, 'permissions']],
    ['post', '/admin/permissions/save', [EveSrp\Controller\AdminController::class, 'savePermissions']],
];
