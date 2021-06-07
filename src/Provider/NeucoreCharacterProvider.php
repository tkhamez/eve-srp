<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use Brave\NeucoreApi\Api\ApplicationCharactersApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Character;
use EveSrp\Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/** @noinspection PhpUnused */
class NeucoreCharacterProvider implements InterfaceCharacterProvider
{
    /**
     * @var ApplicationCharactersApi
     */
    private $api;

    /**
     * @var Character[]|null
     */
    private $characters;

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationCharactersApi::class);
    }

    public function getCharacters(int $eveCharacterId): array
    {
        $this->fetchCharacters($eveCharacterId);

        return array_map(function (Character $char) {
            return $char->getId();
        }, $this->characters);
    }

    public function getMain(int $eveCharacterId): ?int
    {
        try {
            $this->fetchCharacters($eveCharacterId);
        } catch (Exception $e) {
            return null;
        }

        foreach ($this->characters as $character) {
            if ($character->getMain()) {
                return $character->getId();
            }
        }
        return null;
    }

    public function getName(int $eveCharacterId): ?string
    {
        foreach ($this->characters as $character) {
            if ($character->getId() === $eveCharacterId) {
                return $character->getName();
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function fetchCharacters(int $characterId): void
    {
        if ($this->characters !== null) {
            return;
        }
        
        $this->characters = [];
        try {
            $this->characters = $this->api->charactersV1($characterId);
        } catch (ApiException | InvalidArgumentException $e) {
            throw new Exception('NeucoreCharacterProvider::fetchCharacters: ' . $e->getMessage());
        }
    }
}
