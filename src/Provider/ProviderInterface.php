<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\Exception;
use EveSrp\Provider\Data\Account;

interface ProviderInterface
{
    /**
     * Returns the external account with all characters to which the EVE character ID belongs.
     *
     * Returns null if there is no account.
     *
     * This is called after each character login.
     *
     * @throws Exception
     */
    public function getAccount(int $eveCharacterId): ?Account;

    /**
     * Returns groups from external service for the authenticated user.
     *
     * Those groups can be mapped to internal roles and divisions via configuration.
     *
     * This is called after each login and periodically when logged in.
     *
     * @param int $eveCharacterId EVE character ID of logged-in user
     * @return string[] Array of unique group names, e. g. ['submitter', 'admin']
     * @throws Exception
     */
    public function getGroups(int $eveCharacterId): array;

    /**
     * Returns all groups that a character can have.
     *
     * This is called when a global admin syncs the groups.
     *
     * @return string[]
     * @throws Exception
     */
    public function getAvailableGroups(): array;
}
