<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->userService = $container->get(UserService::class);
        $this->requestRepository = $container->get(RequestRepository::class);
    }

    /**
     * @throws \Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find($args['id']);
        $error = null;

        if (! $srpRequest) {
            $error = 'Request not found.';
        } elseif (! $this->userService->maySee($srpRequest)) {
            $srpRequest = null;
            $error = 'Not authorized to view this request.';
        }
        
        $content = $this->twig->render('pages/request.twig', [
            'request' => $srpRequest,
            'error' => $error,
        ]);
        $response->getBody()->write($content);

        return $response;
    }
}
