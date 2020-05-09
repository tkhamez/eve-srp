<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class MyRequestsController
{
    /**
     * @var Environment 
     */
    private $twig;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->userService = $container->get(UserService::class);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->userService->getAuthenticatedUser();

        $content = $this->twig->render('pages/my-requests.twig', ['requests' => $user->getRequests()]);
        $response->getBody()->write($content);

        return $response;
    }
}
