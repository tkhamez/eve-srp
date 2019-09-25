<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Repository\RequestRepository;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class Request
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->requestRepository = $container->get(RequestRepository::class);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find($args['id']);
        
        if (! $srpRequest) {
            return $response->withHeader('Location', '/');
        }
        
        try {
            $content = $this->twig->render('request.twig', ['request' => $srpRequest]);
        } catch (Exception $e) {
            error_log('ApproveController' . $e->getMessage());
            $content = '';
        }
        $response->getBody()->write($content);

        return $response;
    }
}
