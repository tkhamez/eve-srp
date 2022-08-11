<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class MyRequestsController
{
    use TwigResponse;

    private UserService $userService;

    public function __construct(UserService $userService, Environment $environment)
    {
        $this->userService = $userService;

        $this->twigResponse($environment);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->userService->getAuthenticatedUser();

        return $this->render($response, 'pages/my-requests.twig', ['requests' => $user->getRequests()]);
    }
}
