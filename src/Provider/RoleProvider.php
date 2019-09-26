<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\EveSrp\Security;
use Brave\EveSrp\UserService;
use Brave\NeucoreApi\Model\Group;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Maps external groups to roles for an authenticated user.
 */
class RoleProvider implements RoleProviderInterface
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var string[] 
     */
    private $settingsRoles;

    private $roles = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get(UserService::class);
        $this->settingsRoles = $container->get('settings')['ROLE_MAPPING'];
    }

    public function getUserRoles(): array
    {
        return $this->getRoles();
    }

    public function getRoles(ServerRequestInterface $request = null): array
    {
        if (count($this->roles) > 0) {
            return $this->roles;
        }

        $this->roles = [Security::ROLE_ANY];

        $user = $this->userService->getUser();
        if ($user === null) {
            return $this->roles;
        }

        $this->roles[] = Security::ROLE_AUTHENTICATED;
        
        $groups = $user->getExternalGroups();
        $this->roles = array_merge($this->roles, $this->mapGroupsToRoles($groups));

        return $this->roles;
    }

    /**
     * @param Group[] $groups
     * @return array
     */
    private function mapGroupsToRoles(array $groups)
    {
        $requestGroups = explode(',', $this->settingsRoles['submit']);
        $approveGroups = explode(',', $this->settingsRoles['approve']);
        $payGroups = explode(',', $this->settingsRoles['pay']);

        $roles = [];
        foreach ($groups as $group) {
            $groupName = $group->getName();
            if (in_array($groupName, $requestGroups) && ! in_array(Security::ROLE_SUBMIT, $roles)) {
                $roles[] = Security::ROLE_SUBMIT;
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
