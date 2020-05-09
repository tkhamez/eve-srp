<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class SubmitController
{
    /**
     * @var Environment 
     */
    private $twig;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $content = $this->twig->render('pages/submit.twig');
        $response->getBody()->write($content);

        return $response;
    }
}
