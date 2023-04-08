<?php

namespace Test;

use EveSrp\Provider\Data\Account;
use EveSrp\Provider\ProviderInterface;

class TestProvider implements ProviderInterface
{
    public function getAccount(int $eveCharacterId): ?Account
    {
        return null;
    }

    public function getGroups(int $eveCharacterId): array
    {
        return [];
    }

    public function getAvailableGroups(): array
    {
        return [];
    }
}
