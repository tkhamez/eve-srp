<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Model\Permission;
use Brave\EveSrp\Repository\DivisionRepository;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AllRequestsController
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var DivisionRepository
     */
    private $divisionRepository;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->requestRepository = $container->get(RequestRepository::class);
        $this->divisionRepository = $container->get(DivisionRepository::class);
        $this->userService = $container->get(UserService::class);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        # TODO pager

        $selectedStatus = (string) ($request->getQueryParams()['status'] ?? null);
        $selectedDivision = (int) ($request->getQueryParams()['division'] ?? 0);

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

        $content = $this->twig->render('pages/all-requests.twig', [
            'requests' => $requests,
            'divisions' => $divisions,
            'selectedStatus' => $selectedStatus,
            'selectedDivision' => $selectedDivision
        ]);
        $response->getBody()->write($content);

        return $response;
    }
}
