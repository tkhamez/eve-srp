<?php
namespace Brave\EveSrp\Controller;

use Brave\EveSrp\RoleProvider;
use Brave\Sso\Basics\AuthenticationController;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Authentication extends AuthenticationController
{
    /**
     * @var RoleProvider
     */
    private $roleProvider;

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
        
        $this->roleProvider = $container->get(RoleProvider::class);
        $this->sessionHandler = $this->container->get(SessionHandlerInterface::class);
    }

    /**
     * EVE SSO callback.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $ssoV2
     * @return ResponseInterface
     * @throws Exception
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response, $ssoV2 = false)
    {
        parent::auth($request, $response, true);
        $this->roleProvider->clear();

        return $response->withHeader('Location', '/');
    }
    
    public function logout(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->sessionHandler->set('eveAuth', null);
        $this->roleProvider->clear();
        
        return $response->withHeader('Location', '/');
    }
}
