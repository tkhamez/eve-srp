<?php
namespace Brave\CoreConnector;

use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

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
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $response->getBody()->write(str_replace(
            '{{name}}',
            $this->eveAuth ? $this->eveAuth->getCharacterName() : '',
            '7o {{name}}<br><br><a href="/login">login</a>'
        ));

        return $response;
    }
}
