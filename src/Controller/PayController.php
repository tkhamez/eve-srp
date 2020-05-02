<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class PayController
{
    /**
     * @var Environment 
     */
    private $twig;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        try {
            $content = $this->twig->render('pay.twig');
        } catch (Exception $e) {
            error_log('PayController' . $e->getMessage());
            $content = '';
        }

        $response->getBody()->write($content);

        return $response;
    }
}
