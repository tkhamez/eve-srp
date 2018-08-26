<?php
namespace Brave\CoreConnector;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Provides groups from Brave Core from an authenticated user.
 */
class RoleProvider implements RoleProviderInterface
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
    public function getRoles(ServerRequestInterface $request = null)
    {
        #$this->session->set('coreGroups', null);

        /* @var $eveAuth \Brave\Sso\Basics\EveAuthentication */
        $eveAuth = $this->session->get('eveAuth', null);
        if ($eveAuth === null) {
            return [];
        }

        $charId = $eveAuth->getCharacterId();

        $coreGroups = $this->session->get('coreGroups', []);
        if (isset($coreGroups[$charId])) {
            return $coreGroups[$charId];
        }

        try {
            $groups = $this->api->groupsV1($charId);
        } catch (\Exception $e) {
            return [];
        }

        $roles = [];
        foreach ($groups as $group) {
            $roles[] = $group->getName();
        }

        $coreGroups[$charId] = $roles;
        $this->session->set('coreGroups', $coreGroups);

        return $roles;
    }
}
