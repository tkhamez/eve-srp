<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Permission;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Security;
use EveSrp\Misc\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AllRequestsController
{
    use RequestParameter;
    use TwigResponse;

    public function __construct(
        private RequestRepository $requestRepository,
        private DivisionRepository $divisionRepository,
        private UserService $userService,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $selectedStatus = (string)$this->paramGet($request, 'status');
        $selectedDivision = (int)$this->paramGet($request, 'division', '0');
        $currentPage = max(1, ((int)$this->paramGet($request, 'page', '1')));

        $divisions = $this->userService->getDivisionsWithRoles([Permission::REVIEW, Permission::PAY]);

        // check division permission
        $maySeeDivision = false;
        foreach ($divisions as $division) {
            if ($division->getId() === $selectedDivision) {
                $maySeeDivision = true;
                break;
            }
        }
        if (
            !$maySeeDivision &&
            (
                $selectedDivision !== -1 || // -1 = show requests without division ...
                !$this->userService->hasRole(Security::GLOBAL_ADMIN) // ... but only for global admins
            )
        ) {
            $selectedDivision = 0;
        }

        // variables for pager
        $criteria = [
            'status' => $selectedStatus,
            'division' => $selectedDivision === -1 ? null : $selectedDivision
        ];
        $limit = 100;
        $totalRequests = $this->requestRepository->count($criteria);
        $totalPages = ceil($totalRequests / $limit);
        $currentPage = min($totalPages, $currentPage);
        $offset = max(0, ($limit * $currentPage) - $limit);

        return $this->render($response, 'pages/all-requests.twig', [
            'pagerCurrentPage' => $currentPage,
            'pagerTotalPages' => $totalPages,
            'pagerLink' => "?division=$selectedDivision&status=$selectedStatus&page=",
            'requests' => $this->requestRepository->findBy($criteria, ['created' => 'ASC'], $limit, $offset),
            'divisions' => $divisions,
            'selectedStatus' => $selectedStatus,
            'selectedDivision' => $selectedDivision
        ]);
    }
}
