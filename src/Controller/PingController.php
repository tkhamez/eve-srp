<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;

class PingController
{
    /**
     * @var Helper
     */
    private $session;

    public function __construct(ContainerInterface $container)
    {
        $this->session = $container->get(Helper::class);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // prevent session timeout
        $this->session->set('__refresh', time());

        return $response;
    }
}
