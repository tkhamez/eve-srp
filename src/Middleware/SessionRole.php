<?php

namespace Brave\EveSrp\Middleware;

use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tkhamez\Slim\RoleAuth\RoleMiddleware;

/**
 * Adds the user ID and roles to the session.
 * 
 * @see RoleMiddleware
 */
class SessionRole implements MiddlewareInterface
{
    /**
     * @var SessionHandlerInterface 
     */
    private $session;
    
    public function __construct(SessionHandlerInterface $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $roles = $request->getAttribute('roles');
        $roles = is_array($roles) ? $roles : [];
        
        $this->session->set('roles', $roles);

        return $handler->handle($request);
    }
}
