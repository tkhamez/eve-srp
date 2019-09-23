<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CharacterProviderInterface
{
    public function __construct(ContainerInterface $container);
    
    /**
     * Returns all characters from an authenticated user.
     *
     * Example: [96061222, 94737235]
     *
     * @param ServerRequestInterface $request
     * @return int[] Array of EVE character IDs
     */
    public function getCharacters(ServerRequestInterface $request): array;

    /**
     * Remove character IDs from cache, if any.
     */
    public function clear(): void;
}
