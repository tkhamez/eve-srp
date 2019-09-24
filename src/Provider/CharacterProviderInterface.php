<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Psr\Container\ContainerInterface;

interface CharacterProviderInterface
{
    public function __construct(ContainerInterface $container);

    /**
     * Returns all characters from an authenticated user, may or may not including the authenticated user itself.
     *
     * Example: [96061222, 94737235]
     *
     * @return int[] Array of EVE character IDs
     */
    public function getCharacters(): array;

    /**
     * Return the main character ID, if available.
     */
    public function getMain(): ?int;

    /**
     * Return the the name of the character, if available.
     * 
     * @param int $characterId
     * @return string
     */
    public function getName(int $characterId): string;
}
