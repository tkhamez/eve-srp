<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Psr\Container\ContainerInterface;

interface CharacterProviderInterface
{
    public function __construct(ContainerInterface $container);

    /**
     * Return all (other) characters of the user to which the character ID belongs.
     * 
     * The result may or may not include the character IDs from the parameter.
     * THe result may be an empty array if the character is unknown.
     *
     * Example: [96061222, 94737235]
     *
     * @param int $characterId EVE character ID
     * @return int[] Array of EVE character IDs
     */
    public function getCharacters(int $characterId): array;

    /**
     * Return the main character ID, if available.
     * 
     * @param int $characterId EVE character ID
     * @return int|null
     */
    public function getMain(int $characterId): ?int;

    /**
     * Return the the name of the character, if available.
     * 
     * @param int $characterId EVE character ID
     * @return string
     */
    public function getName(int $characterId): string;
}
