<?php
namespace Brave\EveSrp\Controller;

use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class Home
{
    /**
     * @var EveAuthentication
     */
    private $eveAuth;

    /**
     * @var mixed|Environment 
     */
    private $twig;

    public function __construct(ContainerInterface $container) {
        $sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->eveAuth = $sessionHandler->get('eveAuth');
        $this->twig = $container->get(Environment::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     * @throws Exception
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $content = $this->twig->render('home.twig', ['name' => $this->eveAuth->getCharacterName()]);
        $response->getBody()->write($content);

        return $response;
    }
}
