<?php
namespace Brave\CoreConnector;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Provides groups from Brave Core from an authenticated user.
 */
class RoleProvider implements RoleProviderInterface
{
    /**
     * This role is always added.
     */
    const ROLE_ANY = 'role:any';

    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @param ApplicationApi $api
     * @param SessionHandlerInterface $session
     */
    public function __construct(ApplicationApi $api, SessionHandlerInterface $session)
    {
        $this->api = $api;
        $this->session = $session;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string[]
     */
    public function getRoles(ServerRequestInterface $request = null): array
    {
        $roles = [self::ROLE_ANY];

        /* @var EveAuthentication $eveAuth */
        $eveAuth = $this->session->get('eveAuth', null);
        if ($eveAuth === null) {
            return $roles;
        }

        // try cache
        $coreGroups = $this->session->get('coreGroups', null);
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
        foreach ($groups as $group) {
            $roles[] = $group->getName();
        }

        // cache roles
        $this->session->set('coreGroups', [
            'time' => time(),
            'roles' => $roles
        ]);

        return $roles;
    }

    public function clear()
    {
        $this->session->set('coreGroups', null);
    }
}
