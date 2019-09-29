<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\UserService;
use Brave\Sso\Basics\AuthenticationController;
use Brave\Sso\Basics\AuthenticationProvider;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AuthController extends AuthenticationController
{
    /**
     * @var mixed
     */
    private $settings;
    
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var GroupProviderInterface
     */
    private $groupProvider;

    /**
     * @var UserService 
     */
    private $userService;

    /**
     * @var AuthenticationProvider
     */
    private $authenticationProvider;
    
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->settings = $container->get('settings');
        $this->twig = $container->get(Environment::class);
        $this->sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->groupProvider = $container->get(GroupProviderInterface::class);
        $this->userService = $container->get(UserService::class);
        $this->authenticationProvider = $container->get(AuthenticationProvider::class);
    }

    public function login(
        /** @noinspection PhpUnusedParameterInspection */ 
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        try {
            $state = $this->authenticationProvider->generateState();
        } catch (Exception $e) {
            $state = uniqid('srp', true);
        }
        $this->sessionHandler->set('ssoState', $state);

        try {
            $content = $this->twig->render('login.twig', [
                'serviceName' => $this->settings['brave.serviceName'],
                'loginUrl' => $this->authenticationProvider->buildLoginUrl($state),
            ]);
        } catch (Exception $e) {
            error_log('AuthController' . $e->getMessage());
            $content = '';
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * EVE SSO callback.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $ssoV2
     * @return ResponseInterface
     * @throws InvalidArgumentException
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
    ): ResponseInterface {
        $this->sessionHandler->set('userId', null);

        /** @noinspection PhpUnhandledExceptionInspection */
        return $response->withHeader('Location', '/');
    }
}
