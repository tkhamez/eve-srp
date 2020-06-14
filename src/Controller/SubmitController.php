<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Controller\Traits\TwigResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class SubmitController
{
    use TwigResponse;

    public function __construct(ContainerInterface $container)
    {
        $this->twigResponse($container->get(Environment::class));
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($response, 'pages/submit.twig');
    }
}
