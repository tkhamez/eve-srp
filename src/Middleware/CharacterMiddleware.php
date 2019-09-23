<?php

declare(strict_types=1);

namespace Brave\EveSrp\Middleware;

use Brave\EveSrp\Provider\CharacterProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CharacterMiddleware implements MiddlewareInterface
{
    /**
     * @var CharacterProviderInterface
     */
    private $characterProvider;
    
    public function __construct(CharacterProviderInterface $characterProvider)
    {
        $this->characterProvider = $characterProvider;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('chars', $this->characterProvider->getCharacters($request));

        return $handler->handle($request);
    }
}
