<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\FlashMessage;
use EveSrp\Model\Division;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\RequestService;
use EveSrp\Service\UserService;
use EveSrp\Type;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ListsController
{
    use RequestParameter;
    use TwigResponse;

    public function __construct(
        private RequestRepository $requestRepository,
        private UserService $userService,
        private RequestService $requestService,
        private FlashMessage $flashMessage,
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

        return $this->renderView($response, $user->getRequests(), 'down', 'my-requests', 'My Requests');
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
        return $this->showList($response, Type::IN_PROGRESS, Permission::REVIEW, 'in-progress', 'In Progress');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function approved(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->showList($response, Type::APPROVED, Permission::PAY, 'approved', 'Approved');
    }

    public function approvedPayed(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $requestId = (int)$this->paramPost($request, 'id');
        $srpRequest = $this->requestRepository->find($requestId);

        if (!$srpRequest) {
            $this->flashMessage->addMessage('Request not found.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', '/approved')->withStatus(302);
        }

        if (!$this->requestService->validateInputAndPermission($srpRequest, newStatus: Type::PAID)) {
            $this->flashMessage->addMessage('You are not allowed to pay this request.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', '/approved')->withStatus(302);
        }

        $this->requestService->save($srpRequest, newStatus: Type::PAID);
        $this->flashMessage->addMessage(
            "Success, paid request [$requestId](/request/$requestId).",
            FlashMessage::TYPE_SUCCESS
        );

        return $response->withHeader('Location', '/approved')->withStatus(302);
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

        return $this->renderView($response, $requests, 'up', $page, $pageName);
    }

    private function renderView($response, $requests, $sortOrder, $page, $pageName): ResponseInterface
    {
        return $this->render($response, 'pages/list.twig', [
            'requests' => $requests,
            'payoutSum' => $this->calculatePayoutSum($requests),
            'sortOrder' => $sortOrder,
            'pageActive' => $page,
            'pageName' => $pageName,
        ]);
    }

    /**
     * @param Request[] $requests
     */
    private function calculatePayoutSum(array $requests): ?int
    {
        if (empty($requests)) {
            return null;
        }

        $sum = 0;
        foreach ($requests as $request) {
            $sum += $request->getPayout();
        }
        return $sum;
    }
}
