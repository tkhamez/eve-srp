<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PingController
{
    /**
     * @var SessionHandlerInterface
     */
    private $session;

    public function __construct(ContainerInterface $container)
    {
        $this->session = $container->get(SessionHandlerInterface::class);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // prevent session timeout
        $this->session->set('__refresh', time());

        return $response;
    }
}
