<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Character;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
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

    public function getCharacters(): array
    {
        $this->fetchCharacters();

        return array_map(function (Character $char) {
            return $char->getId();
        }, $this->characters);
    }

    public function getMain(): ?int
    {
        $this->fetchCharacters();

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
    
    private function fetchCharacters(): void
    {
        /* @var EveAuthentication $eveAuth */
        $eveAuth = $this->session->get('eveAuth', null);
        
        if ($eveAuth === null) {
            $this->characters = [];
            return;
        }

        if ($this->characters !== null) {
            return;
        }
        
        $this->characters = [];
        try {
            $this->characters = $this->api->charactersV1($eveAuth->getCharacterId());
        } catch (ApiException $e) {
            // Don't log "404 Character not found." error from Core.
            if ($e->getCode() !== 404 || strpos($e->getMessage(), 'Character not found.') === false) {
                error_log('NeucoreCharacterProvider::getCharacters:' . $e->getMessage());
            }
        }
    }
}
