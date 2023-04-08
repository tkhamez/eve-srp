<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Division;
use EveSrp\Model\Permission;
use EveSrp\Repository\RequestRepository;
use EveSrp\Type;
use EveSrp\Misc\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ProcessListsController
{
    use TwigResponse;

    public function __construct(
        private RequestRepository $requestRepository,
        private UserService $userService,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function review(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
       return $this->showPage($response, Type::EVALUATING, Permission::REVIEW, 'review.twig');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function pay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->showPage($response, Type::APPROVED, Permission::PAY, 'pay.twig');
    }

    private function showPage($response, $status, $role, $page): ResponseInterface
    {
        $divisions = array_map(function (Division $division) {
            return $division->getId();
        }, $this->userService->getDivisionsWithRoles([$role]));

        $requests = $this->requestRepository->findBy([
            'status' => $status,
            'division' => $divisions
        ], ['created' => 'ASC']);

        return $this->render($response, "pages/$page", ['requests' => $requests]);
    }
}
