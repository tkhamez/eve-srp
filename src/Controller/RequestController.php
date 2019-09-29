<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\UserService;
use Exception;
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

    public function show(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response, 
                                                          $args
    ): ResponseInterface {
        $srpRequest = $this->requestRepository->find($args['id']);
        
        if (! $srpRequest) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $response->withHeader('Location', '/');
        }
        
        if (! $this->userService->maySee($srpRequest)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $response->withHeader('Location', '/');
        }
        
        try {
            $content = $this->twig->render('request.twig', ['request' => $srpRequest]);
        } catch (Exception $e) {
            error_log('RequestController' . $e->getMessage());
            $content = '';
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $response->getBody()->write($content);

        return $response;
    }
}
