<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait RequestParameter
{
    /**
     * @param ServerRequestInterface $request
     * @param string $key
     * @param string|array|null $default
     * @return string|array|null
     */
    protected function paramGet(ServerRequestInterface $request, string $key, $default = null)
    {
        return $request->getQueryParams()[$key] ?? $default;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    protected function paramPost(ServerRequestInterface $request, string $key, $default = null)
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
