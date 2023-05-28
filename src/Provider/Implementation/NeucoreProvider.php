<?php

declare(strict_types=1);

namespace EveSrp\Provider\Implementation;

use Brave\NeucoreApi\Api\ApplicationCharactersApi;
use EveSrp\Exception;
use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\Api\ApplicationGroupsApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Group;
use EveSrp\Provider\ProviderInterface;
use EveSrp\Provider\Data\Account;
use EveSrp\Provider\Data\Character;
use InvalidArgumentException;

/** @noinspection PhpUnused */
class NeucoreProvider implements ProviderInterface
{
    private ApplicationApi $applicationApi;

    private ApplicationCharactersApi $characterApi;

    private ApplicationGroupsApi $groupApi;

    public function __construct(
        ApplicationApi $applicationApi,
        ApplicationCharactersApi $characterApi,
        ApplicationGroupsApi $groupApi
    ) {
        $this->applicationApi = $applicationApi;
        $this->characterApi = $characterApi;
        $this->groupApi = $groupApi;
    }

    public function getAccount(int $eveCharacterId): ?Account
    {
        try {
            $coreAccount = $this->characterApi->playerWithCharactersV1($eveCharacterId);
        } catch (ApiException | InvalidArgumentException $e) {
            throw new Exception(__METHOD__ . ': ' . $e->getMessage());
        }

        $account = new Account((string)$coreAccount->getId());
        foreach ($coreAccount->getCharacters() as $character) {
            $account->addCharacter(new Character(
                $character->getId(),
                $character->getName(),
                $character->getMain(),
            ));
        }

        return $account;
    }

    public function getGroups(int $eveCharacterId): array
    {
        try {
            $groups = $this->groupApi->groupsV2($eveCharacterId);
        } catch (ApiException | InvalidArgumentException $e) {
            throw new Exception(__METHOD__ . ': ' . $e->getMessage());
        }

        return array_map(function (Group $group) {
            return $group->getName();
        }, $groups);
    }

    public function getAvailableGroups(): array
    {
        try {
            $app = $this->applicationApi->showV1();
        } catch (ApiException | InvalidArgumentException $e) {
            throw new Exception(__METHOD__ . ': ' . $e->getMessage());
        }

        return array_map(function (Group $group) {
            return $group->getName();
        }, $app->getGroups());
    }
}
