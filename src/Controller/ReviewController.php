<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Model\Division;
use Brave\EveSrp\Model\Permission;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\Type;
use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class ReviewController
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
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->requestRepository = $container->get(RequestRepository::class);
        $this->userService = $container->get(UserService::class);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = array_map(function (Division $division) {
            return $division->getId();
        }, $this->userService->getDivisionsWithRoles([Permission::REVIEW]));

        $requests = $this->requestRepository->findBy([
            'status' => Type::EVALUATING,
            'division' => $divisions
        ], ['created' => 'ASC']);

        $content = $this->twig->render('pages/review.twig', ['requests' => $requests]);
        $response->getBody()->write($content);

        return $response;
    }
}
