<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\SrpException;

interface GroupProviderInterface
{
    /**
     * Returns groups from external service for the authenticated user.
     * 
     * Those groups are mapped to internal roles and divisions via configuration.
     *
     * This is called after each character login.
     * 
     * @param int $eveCharacterId EVE character ID
     * @throws SrpException
     * @return string[] Array of unique group names, e. g. ['submitter', 'admin']
     */
    public function getGroups(int $eveCharacterId): array;

    /**
     * Returns all groups that a character can have.
     *
     * This can be called manually by an admin.
     *
     * @throws SrpException
     * @return string[]
     */
    public function getAvailableGroups(): array;
}
