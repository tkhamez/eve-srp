<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Permission;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\UserService;
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
        # TODO pager

        $selectedStatus = (string) $this->paramGet($request, 'status');
        $selectedDivision = (int) $this->paramGet($request, 'division', '0');

        $divisions = $this->userService->getDivisionsWithRoles([Permission::REVIEW, Permission::PAY]);

        // check division permission
        $maySeeDivision = false;
        foreach ($divisions as $division) {
            if ($division->getId() === $selectedDivision) {
                $maySeeDivision = true;
                break;
            }
        }
        if (! $maySeeDivision) {
            $selectedDivision = 0;
        }

        $requests = $this->requestRepository->findBy([
            'status' => $selectedStatus,
            'division' => $selectedDivision
        ], ['created' => 'ASC']);

        return $this->render($response, 'pages/all-requests.twig', [
            'requests' => $requests,
            'divisions' => $divisions,
            'selectedStatus' => $selectedStatus,
            'selectedDivision' => $selectedDivision
        ]);
    }
}
