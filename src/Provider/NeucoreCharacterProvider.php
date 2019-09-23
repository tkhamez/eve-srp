<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Character;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationApi::class);
        $this->session = $container->get(SessionHandlerInterface::class);
    }

    public function getCharacters(ServerRequestInterface $request): array
    {
        /* @var EveAuthentication $eveAuth */
        $eveAuth = $this->session->get('eveAuth', null);
        if ($eveAuth === null) {
            return [];
        }

        // try cache
        $coreChars = $this->session->get('NeucoreCharacterProvider_chars', null);
        if (is_array($coreChars) && $coreChars['time'] > (time() - 60*60)) {
            return $coreChars['chars'];
        }

        $characters = [];
        try {
            $characters = $this->api->charactersV1($eveAuth->getCharacterId());
        } catch (ApiException $e) {
            error_log('NeucoreCharacterProvider::getCharacters:' . $e->getMessage());
        }

        $chars = array_map(function (Character $char) {
            return $char->getId();
        }, $characters);

        // cache chars
        $this->session->set('NeucoreCharacterProvider_chars', [
            'time' => time(),
            'chars' => $chars
        ]);
        
        return $chars;
    }

    /**
     * Remove character IDs from cache, if any.
     */
    public function clear(): void
    {
        $this->session->set('NeucoreCharacterProvider_chars', null);
    }
}
