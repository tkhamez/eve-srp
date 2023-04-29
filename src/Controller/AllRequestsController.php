<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Character;
use EveSrp\Model\Division;
use EveSrp\Model\User;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Service\RequestService;
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
        private UserRepository $userRepository,
        private CharacterRepository $characterRepository,
        private UserService $userService,
        private RequestService $requestService,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = $this->requestService->getDivisionsWithEditPermission();

        if ($this->paramGet($request, 'submit') !== null) {
            $inputStatus = (string)$this->paramGet($request, 'status');
            $inputDivision = (string)$this->paramGet($request, 'division', '');
            $inputShip = trim((string)$this->paramGet($request, 'ship'));
            $inputPilot = trim((string)$this->paramGet($request, 'pilot'));
            $inputCorporation = trim((string)$this->paramGet($request, 'corporation'));
            $inputUser = trim((string)$this->paramGet($request, 'user'));
            $currentPage = max(1, ((int)$this->paramGet($request, 'page', '1')));

            // check division permission
            $allDivisions = array_map(function (Division $division) {
                return $division->getId();
            }, $divisions);
            if ($inputDivision > 0 && !in_array($inputDivision, $allDivisions)) {
                $inputDivision = -2; // shows nothing
            }

            // Search criteria and variables for pager.
            $criteria = [];
            if ($inputStatus !== '') {
                $criteria['status'] = $inputStatus;
            }
            if ($inputDivision === '') {
                $criteria['division'] = $allDivisions;
            } else {
                $criteria['division'] = $inputDivision === '-1' ? null : (int)$inputDivision;
            }
            if (mb_strlen($inputShip) > 2) {
                $criteria['ship'] = "$inputShip%";
            } else {
                $inputShip = '';
            }
            if (mb_strlen($inputPilot) > 2) {
                $criteria['character'] = $this->getCharacterIds("$inputPilot%");
            } else {
                $inputPilot = '';
            }
            if (mb_strlen($inputCorporation) > 2) {
                $criteria['corporationName'] = "$inputCorporation%";
            } else {
                $inputCorporation = '';
            }
            if (mb_strlen($inputUser) > 2) {
                $criteria['user'] = $this->getUserIds("$inputUser%");
            } else {
                $inputUser = '';
            }
            $limit = 100;
            $totalRequests = $this->requestRepository->countByCriteria($criteria);
            $totalPages = ceil($totalRequests / $limit);
            $currentPage = min($totalPages, $currentPage);
            $offset = (int)max(0, ($limit * $currentPage) - $limit);

            $requests = $this->requestRepository->findByCriteria($criteria, $limit, $offset);
            $payoutSum = $this->requestRepository->sumPayout($criteria);

            $pagerLink = "?division=$inputDivision&status=$inputStatus&ship=$inputShip&pilot=$inputPilot" .
                "&corporation=$inputCorporation&user=$inputUser&submit&page=";
        }

        return $this->render($response, 'pages/all-requests.twig', [
            'divisions' => $divisions,
            'inputDivision' => $inputDivision ?? 0,
            'inputStatus' => $inputStatus ?? '',
            'inputShip' => $inputShip ?? null,
            'inputCorporation' => $inputCorporation ?? null,
            'inputUser' => $inputUser ?? null,
            'inputPilot' => $inputPilot ?? null,
            'requests' => $requests ?? [],
            'payoutSum' => $payoutSum ?? null,
            'pagerCurrentPage' => $currentPage ?? 0,
            'pagerTotalPages' => $totalPages ?? 0,
            'pagerLink' => $pagerLink ?? '',
        ]);
    }

    /**
     * @return int[]
     */
    private function getUserIds(string $name): array
    {
        return array_map(function (User $user) {
            return $user->getId();
        }, $this->userRepository->findByName($name));
    }

    /**
     * @return int[]
     */
    private function getCharacterIds(string $name): array
    {
        return array_map(function (Character $user) {
            return $user->getId();
        }, $this->characterRepository->findByName($name));
    }
}
