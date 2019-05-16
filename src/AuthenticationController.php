<?php
namespace Brave\CoreConnector;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationController extends \Brave\Sso\Basics\AuthenticationController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $ssoV2
     * @return ResponseInterface
     * @throws \Exception
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response, $ssoV2 = false)
    {
        parent::auth($request, $response); // SSO v1
        #parent::auth($request, $response, true); // SSO v2

        return $response->withHeader('Location', '/');
    }
}
