<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;

class PingController
{
    public function __construct(private Helper $session)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // prevent session timeout
        $this->session->set('__refresh', time());

        return $response;
    }
}
