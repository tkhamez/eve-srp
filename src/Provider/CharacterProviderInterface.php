<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\SrpException;
use Psr\Container\ContainerInterface;

interface CharacterProviderInterface
{
    public function __construct(ContainerInterface $container);

    /**
     * Return all (other) characters of the user to which the character ID belongs.
     * 
     * The result may or may not include the character IDs from the parameter.
     * The result may be an empty array if the character is unknown.
     *
     * This is called after each character login.
     *
     * @param int $characterId EVE character ID
     * @throws SrpException
     * @return int[] Array of EVE character IDs
     */
    public function getCharacters(int $characterId): array;

    /**
     * Return the main character ID of the user to which the character ID belongs, if available.
     * 
     * @param int $characterId EVE character ID
     * @return int|null
     */
    public function getMain(int $characterId): ?int;

    /**
     * Return the the name of the character, if available.
     * 
     * @param int $characterId EVE character ID
     * @return string|null
     */
    public function getName(int $characterId): ?string;
}
