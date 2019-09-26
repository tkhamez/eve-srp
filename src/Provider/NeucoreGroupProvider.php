<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Group;
use Psr\Container\ContainerInterface;

/** @noinspection PhpUnused */
class NeucoreGroupProvider implements GroupProviderInterface
{
    /**
     * @var ApplicationApi
     */
    private $api;

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationApi::class);
    }

    public function getGroups(int $eveCharacterId): array
    {
        $groups = [];
        
        // get groups from Core
        try {
            $groups = $this->api->groupsV2($eveCharacterId);
        } catch (ApiException $ae) {
            // Don't log "404 Character not found." error from Core.
            if ($ae->getCode() !== 404 || strpos($ae->getMessage(), 'Character not found.') === false) {
                error_log('NeucoreRoleProvider::getRoles: ' . $ae->getMessage());
            }
        }

        return array_map(function (Group $group) {
            return $group->getName();
        }, $groups);
    }
}
