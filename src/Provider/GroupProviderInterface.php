<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\Exception;

interface GroupProviderInterface
{
    /**
     * Returns groups from external service for the authenticated user.
     * 
     * Those groups can be mapped to internal roles and divisions via configuration.
     *
     * This is called after each character login.
     * 
     * @param int $eveCharacterId EVE character ID of logged in user
     * @return string[] Array of unique group names, e. g. ['submitter', 'admin']
     * @throws Exception
     */
    public function getGroups(int $eveCharacterId): array;

    /**
     * Returns all groups that a character can have.
     *
     * This can be called manually by an admin.
     *
     * @return string[]
     * @throws Exception
     */
    public function getAvailableGroups(): array;
}
