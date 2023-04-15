<?php

declare(strict_types=1);

return [
    ['get', '/login',  [EveSrp\Controller\AuthController::class, 'login']],
    ['get', '/auth',   [EveSrp\Controller\AuthController::class, 'auth']],
    ['get', '/logout', [EveSrp\Controller\AuthController::class, 'logout']],
    ['get', '/ping',   EveSrp\Controller\PingController::class],

    ['get',  '/',             [EveSrp\Controller\ListsController::class, 'myRequests']],
    ['get',  '/my-requests',  [EveSrp\Controller\ListsController::class, 'myRequests']],
    ['get',  '/review',       [EveSrp\Controller\ListsController::class, 'review']],
    ['get',  '/pay',          [EveSrp\Controller\ListsController::class, 'pay']],
    ['get',  '/request/{id}', [EveSrp\Controller\RequestController::class, 'show']],
    ['get',  '/submit',       [EveSrp\Controller\SubmitController::class, 'showForm']],
    ['post', '/submit',       [EveSrp\Controller\SubmitController::class, 'submitForm']],
    ['get',  '/all-requests', EveSrp\Controller\AllRequestsController::class],

    ['get',  '/admin/divisions',        [EveSrp\Controller\AdminController::class, 'divisions']],
    ['post', '/admin/divisions/new',    [EveSrp\Controller\AdminController::class, 'newDivision']],
    ['post', '/admin/divisions/delete', [EveSrp\Controller\AdminController::class, 'deleteDivision']],
    ['get',  '/admin/groups',           [EveSrp\Controller\AdminController::class, 'groups']],
    ['post', '/admin/groups/sync',      [EveSrp\Controller\AdminController::class, 'syncGroups']],
    ['get',  '/admin/permissions',      [EveSrp\Controller\AdminController::class, 'permissions']],
    ['post', '/admin/permissions/save', [EveSrp\Controller\AdminController::class, 'savePermissions']],
];
