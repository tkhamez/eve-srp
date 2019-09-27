<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\UserService;
use Brave\Sso\Basics\AuthenticationController;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends AuthenticationController
{
    /**
     * @var GroupProviderInterface
     */
    private $groupProvider;

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
        
        $this->groupProvider = $container->get(GroupProviderInterface::class);
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
            error_log('AuthController::auth: ' . $e->getMessage());
        }
        
        /* @var EveAuthentication $eveAuth */
        $eveAuth = $this->sessionHandler->get('eveAuth');
        $this->sessionHandler->set('eveAuth', null);
        
        $user = $this->userService->syncCharacters($eveAuth);
        $this->userService->syncGroups($eveAuth->getCharacterId(), $user);
        $this->sessionHandler->set('userId', $user->getId());

        return $response->withHeader('Location', '/');
    }

    /** @noinspection PhpUnused */
    public function logout(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response
    ) {
        $this->sessionHandler->set('userId', null);
        
        return $response->withHeader('Location', '/');
    }
}
