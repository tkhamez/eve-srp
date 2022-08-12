<?php

declare(strict_types=1);

namespace EveSrp\Controller\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait RequestParameter
{
    protected function paramGet(
        ServerRequestInterface $request,
        string $key,
        array|string $default = null
    ): array|string|null {
        return $request->getQueryParams()[$key] ?? $default;
    }

    protected function paramPost(ServerRequestInterface $request, string $key, array|string $default = null): mixed
    {
        $postParams = $request->getParsedBody();
        if (is_array($postParams) && isset($postParams[$key])) {
            return $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            return $postParams->$key;
        }
        return $default;
    }
}
