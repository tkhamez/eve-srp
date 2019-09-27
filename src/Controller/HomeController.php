<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\UserService;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class HomeController
{
    /**
     * @var Environment 
     */
    private $twig;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->userService = $container->get(UserService::class);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        $user = $this->userService->getAuthenticatedUser();

        #var_Dump(array_map(function($i) { return $i->getName(); }, $user->getExternalGroups()));
        #var_Dump($this->userService->getClientRoles());
        #var_Dump(array_map(function($i) { return $i->getDivision()->getName() .': '. $i->getPermission(); }, $this->userService->getUserPermissions()));

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
