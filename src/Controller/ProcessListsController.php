<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Division;
use EveSrp\Model\Permission;
use EveSrp\Repository\RequestRepository;
use EveSrp\Type;
use EveSrp\Service\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ProcessListsController
{
    use TwigResponse;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container)
    {
        $this->requestRepository = $container->get(RequestRepository::class);
        $this->userService = $container->get(UserService::class);

        $this->twigResponse($container->get(Environment::class));
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

    private function showPage($response, $status, $role, $page)
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
