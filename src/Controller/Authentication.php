<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Provider\RoleProviderInterface;
use Brave\EveSrp\UserService;
use Brave\Sso\Basics\AuthenticationController;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Authentication extends AuthenticationController
{
    /**
     * @var RoleProviderInterface
     */
    private $roleProvider;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var UserService 
     */
    private $userService;

    /**
     * @var string
     */
    protected $template = ROOT_DIR . '/templates/login.html';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        
        $this->roleProvider = $container->get(RoleProviderInterface::class);
        $this->sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->userService = $container->get(UserService::class);
    }

    /**
     * EVE SSO callback.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $ssoV2
     * @return ResponseInterface
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response, $ssoV2 = false)
    {
        try {
            $response = parent::auth($request, $response, true);
        } catch (Exception $e) {
            error_log('Authentication::auth: ' . $e->getMessage());
        }
        $this->roleProvider->clear();

        $user = $this->userService->syncCharacters($this->sessionHandler->get('eveAuth'));
        $this->sessionHandler->set('userId', $user->getId());

        return $response->withHeader('Location', '/');
    }

    /** @noinspection PhpUnused */
    public function logout(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response
    ) {
        $this->sessionHandler->set('eveAuth', null);
        $this->roleProvider->clear();
        
        return $response->withHeader('Location', '/');
    }
}
