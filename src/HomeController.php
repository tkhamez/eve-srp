<?php
namespace Brave\CoreConnector;

use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    /**
     * @var EveAuthentication|null
     */
    private $eveAuth;

    public function __construct(ContainerInterface $container) {
        $sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->eveAuth = $sessionHandler->get('eveAuth');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $response->getBody()->write(str_replace(
            '{{name}}',
            $this->eveAuth ? $this->eveAuth->getCharacterName() : '',
            '7o {{name}}<br>
                <br>
                <a href="/login">login</a><br>
                <a href="/secured">secured</a> (only works if middleware is enabled in Bootstrap class)<br>
                <a href="/logout">logout</a>'
        ));

        return $response;
    }
}
