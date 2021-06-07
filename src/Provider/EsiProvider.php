<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;

/**
 * A very simple group and character provider.
 *
 * Groups are assigned based on alliance and/or corporation membership for submitter
 * and character IDs for other groups.
 *
 * This does not support alternative characters.
 *
 * @noinspection PhpUnused
 */
class EsiProvider implements InterfaceCharacterProvider, InterfaceGroupProvider
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $esiBaseUrl;

    public function __construct(ContainerInterface $container)
    {
        $this->httpClient = $container->get(ClientInterface::class);
        $this->esiBaseUrl = $container->get('settings')['ESI_BASE_URL'];
    }

    public function getCharacters(int $eveCharacterId): array
    {
        return [];
    }

    public function getMain(int $eveCharacterId): ?int
    {
        return null;
    }

    public function getName(int $eveCharacterId): ?string
    {
        return null;
    }

    public function getGroups(int $eveCharacterId): array
    {
        try {
            $result = $this->httpClient->request(
                'GET',
                "{$this->esiBaseUrl}latest/characters/$eveCharacterId/?datasource=tranquility"
            );
        } catch (GuzzleException $e) {
            throw new Exception('EsiProvider::getGroups: ' . $e->getMessage());
        }

        $userData = \json_decode($result->getBody()->getContents());

        $submitterAlliances = explode(',', (string) $_ENV['EVE_SRP_ESI_SUBMITTER_ALLIANCES']);
        $submitterCorporations = explode(',', (string) $_ENV['EVE_SRP_ESI_SUBMITTER_CORPORATIONS']);
        $reviewChars = explode(',', (string) $_ENV['EVE_SRP_ESI_REVIEW_CHARACTERS']);
        $payChars = explode(',', (string) $_ENV['EVE_SRP_ESI_PAY_CHARACTERS']);
        $adminChars = explode(',', (string) $_ENV['EVE_SRP_ESI_ADMIN_CHARACTERS']);
        $globalAdminChars = explode(',', (string) $_ENV['EVE_SRP_ESI_GLOBAL_ADMIN_CHARACTERS']);

        $groups = [];
        if (
            in_array($userData->alliance_id, $submitterAlliances) ||
            in_array($userData->corporation_id, $submitterCorporations)
        ) {
            $groups[] = 'member';
        }
        if (in_array($eveCharacterId, $reviewChars)) {
            $groups[] = 'review';
        }
        if (in_array($eveCharacterId, $payChars)) {
            $groups[] = 'pay';
        }
        if (in_array($eveCharacterId, $adminChars)) {
            $groups[] = 'admin';
        }
        if (in_array($eveCharacterId, $globalAdminChars)) {
            $groups[] = 'global-admin';
        }

        return $groups;
    }

    public function getAvailableGroups(): array
    {
        return ['member', 'review', 'pay', 'admin', 'global-admin'];
    }
}
