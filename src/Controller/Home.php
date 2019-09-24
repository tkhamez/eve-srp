<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Repository\UserRepository;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class Home
{
    /**
     * @var mixed|Environment 
     */
    private $twig;

    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->session = $container->get(SessionHandlerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        $user = $this->userRepository->find($this->session->get('userId'));

        try {
            $content = $this->twig->render('home.twig', ['requests' => $user->getRequests()]);
        } catch (Exception $e) {
            error_log('HomeController' . $e->getMessage());
            $content = '';
        }
        $response->getBody()->write($content);

        return $response;
    }
}
