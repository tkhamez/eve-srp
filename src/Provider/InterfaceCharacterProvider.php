<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\Exception;

interface InterfaceCharacterProvider
{
    /**
     * Returns all (other) characters of the user to which the character ID belongs.
     * 
     * The result may or may not include the character ID from the parameter.
     * The result may be an empty array if the character is unknown.
     *
     * This is called after each character login.
     *
     * @param int $eveCharacterId EVE character ID
     * @return int[] Array of EVE character IDs
     * @throws Exception
     */
    public function getCharacters(int $eveCharacterId): array;

    /**
     * Returns the main character ID of the user to which the character ID belongs, if available.
     * 
     * @param int $eveCharacterId EVE character ID
     * @return int|null
     */
    public function getMain(int $eveCharacterId): ?int;

    /**
     * Returns the name of the character, if available.
     * 
     * @param int $eveCharacterId EVE character ID
     * @return string|null
     */
    public function getName(int $eveCharacterId): ?string;
}
