<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\RoleProviderInterface;
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
     * @var CharacterProviderInterface
     */
    private $charProvider;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var string
     */
    protected $template = ROOT_DIR . '/templates/login.html';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        
        $this->roleProvider = $container->get(RoleProviderInterface::class);
        $this->charProvider = $container->get(CharacterProviderInterface::class);
        $this->sessionHandler = $this->container->get(SessionHandlerInterface::class);
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
            parent::auth($request, $response, true);
        } catch (Exception $e) {
            error_log('Authentication::auth: ' . $e->getMessage());
        }
        $this->roleProvider->clear();
        $this->charProvider->clear();

        return $response->withHeader('Location', '/');
    }

    /** @noinspection PhpUnused */
    public function logout(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response
    ) {
        $this->sessionHandler->set('eveAuth', null);
        $this->roleProvider->clear();
        $this->charProvider->clear();
        
        return $response->withHeader('Location', '/');
    }
}
