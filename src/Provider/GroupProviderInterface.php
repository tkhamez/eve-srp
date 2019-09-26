<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

interface GroupProviderInterface
{
    /**
     * Returns groups from external service for the authenticated user.
     * 
     * Those groups are mapped to internal roles and divisions via configuration.
     * 
     * @param int $eveCharacterId
     * @return string[]
     */
    public function getGroups(int $eveCharacterId): array;
    
    // TODO add getAvailableGroups() (= all groups that a user could have)? 
}
