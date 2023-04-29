<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Division;
use EveSrp\Model\Permission;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\RequestService;
use EveSrp\Service\UserService;
use EveSrp\Type;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ListsController
{
    use TwigResponse;

    public function __construct(
        private RequestRepository $requestRepository,
        private UserService $userService,
        private RequestService $requestService,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function myRequests(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->userService->getAuthenticatedUser();

        return $this->renderView($response, $user->getRequests(), 'my-requests', 'My Requests');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function open(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
       return $this->showList($response, Type::OPEN, Permission::REVIEW, 'open', 'Open Requests');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function inProgress(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->showList($response, Type::IN_PROGRESS, Permission::REVIEW, 'in_progress', 'In Progress');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function pay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->showList($response, Type::APPROVED, Permission::PAY, 'pay', 'Pay');
    }

    private function showList($response, $status, $role, $page, $pageName): ResponseInterface
    {
        $divisions = array_map(function (Division $division) {
            return $division->getId();
        }, $this->userService->getDivisionsWithRoles([$role]));

        $requests = $this->requestRepository->findBy([
            'status' => $status,
            'division' => $divisions
        ], ['created' => 'ASC']);

        return $this->renderView($response, $requests, $page, $pageName);
    }

    private function renderView($response, $requests, $page, $pageName): ResponseInterface
    {
        return $this->render($response, 'pages/list.twig', [
            'requests' => $requests,
            'payoutSum' => $this->requestService->calculatePayoutSum($requests),
            'pageActive' => $page,
            'pageName' => $pageName,
        ]);
    }
}
