<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\EveSrp\Security;
use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Group;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides groups from Brave Core from an authenticated user.
 */
class NeucoreRoleProvider implements RoleProviderInterface
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
     * @var string[] 
     */
    private $settingsRoles;

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationApi::class);
        $this->session = $container->get(SessionHandlerInterface::class);
        $this->settingsRoles = $container->get('settings')['ROLE_MAPPING'];
    }

    /**
     * @param ServerRequestInterface $request
     * @return string[]
     */
    public function getRoles(ServerRequestInterface $request = null): array
    {
        $roles = [Security::ROLE_ANY];

        /* @var EveAuthentication $eveAuth */
        $eveAuth = $this->session->get('eveAuth', null);
        if ($eveAuth === null) {
            return $roles;
        }

        $roles[] = Security::ROLE_AUTHENTICATED;

        // try cache
        $coreGroups = $this->session->get('NeucoreRoleProvider_groups', null);
        if (is_array($coreGroups) && $coreGroups['time'] > (time() - 60*60)) {
            return $coreGroups['roles'];
        }

        // get groups from Core
        try {
            $groups = $this->api->groupsV2($eveAuth->getCharacterId());
        } catch (ApiException $ae) {
            // Don't log "404 Character not found." error from Core.
            if ($ae->getCode() !== 404 || strpos($ae->getMessage(), 'Character not found.') === false) {
                error_log((string)$ae);
            }
            return $roles;
        }
        $roles = array_merge($roles, $this->mapGroupsToRoles($groups));

        // cache roles
        $this->session->set('NeucoreRoleProvider_groups', [
            'time' => time(),
            'roles' => $roles
        ]);

        return $roles;
    }

    public function clear(): void
    {
        $this->session->set('NeucoreRoleProvider_groups', null);
    }

    /**
     * @param Group[] $groups
     * @return array
     */
    private function mapGroupsToRoles(array $groups)
    {
        $requestGroups = explode(',', $this->settingsRoles['request']);
        $approveGroups = explode(',', $this->settingsRoles['approve']);
        $payGroups = explode(',', $this->settingsRoles['pay']);

        $roles = [];
        foreach ($groups as $group) {
            $groupName = $group->getName();
            if (in_array($groupName, $requestGroups) && ! in_array(Security::ROLE_REQUEST, $roles)) {
                $roles[] = Security::ROLE_REQUEST;
            }
            if (in_array($groupName, $approveGroups) && ! in_array(Security::ROLE_APPROVE, $roles)) {
                $roles[] = Security::ROLE_APPROVE;
            }
            if (in_array($groupName, $payGroups) && ! in_array(Security::ROLE_PAY, $roles)) {
                $roles[] = Security::ROLE_PAY;
            }
        }
        
        return $roles;
    }
}
