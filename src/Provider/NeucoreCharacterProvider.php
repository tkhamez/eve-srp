<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Character;
use Brave\Sso\Basics\SessionHandlerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/** @noinspection PhpUnused */
class NeucoreCharacterProvider implements CharacterProviderInterface
{
    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @var Character[]|null
     */
    private $characters;

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationApi::class);
        $this->session = $container->get(SessionHandlerInterface::class);
    }

    public function getCharacters(int $characterId): array
    {
        $this->fetchCharacters($characterId);

        return array_map(function (Character $char) {
            return $char->getId();
        }, $this->characters);
    }

    public function getMain(int $characterId): ?int
    {
        $this->fetchCharacters($characterId);

        foreach ($this->characters as $character) {
            if ($character->getMain()) {
                return $character->getId();
            }
        }
        return null;
    }

    public function getName(int $characterId): string 
    {
        foreach ($this->characters as $character) {
            if ($character->getId() === $characterId) {
                return $character->getName();
            }
        }
        return '';
    }
    
    private function fetchCharacters(int $characterId): void
    {
        if ($this->characters !== null) {
            return;
        }
        
        $this->characters = [];
        try {
            $this->characters = $this->api->charactersV1($characterId);
        } catch (ApiException $ae) {
            // Don't log "404 Character not found." error from Core.
            if ($ae->getCode() !== 404 || strpos($ae->getMessage(), 'Character not found.') === false) {
                error_log('NeucoreCharacterProvider::getCharacters:' . $ae->getMessage());
            }
        } catch (InvalidArgumentException $e) {
            error_log('NeucoreCharacterProvider::getCharacters:' . $e->getMessage());
        }
    }
}
