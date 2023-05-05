<?php

declare(strict_types=1);

namespace EveSrp\Misc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use SlimSession\Helper;

class CSRFTokenMiddleware implements MiddlewareInterface
{
    public const CSRF_KEY_NAME = 'csrfToken';

    public function __construct(private Helper $session)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionToken = $this->session->get(self::CSRF_KEY_NAME);
        $bodyToken = $request->getParsedBody()[self::CSRF_KEY_NAME] ?? '';

        if ($request->getMethod() === 'POST' && (empty($sessionToken) || $sessionToken !== $bodyToken)) {
            throw new HttpForbiddenException($request);
        }

        return $handler->handle($request);
    }
}
