<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\EveSrp\Model\Permission;
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

    /**
     * Returns roles of user (authenticated or not)
     */
    public function getClientRoles(): array
    {
        return $this->getRoles();
    }

    public function getRoles(ServerRequestInterface $request = null): array
    {
        if (count($this->roles) > 0) {
            return $this->roles;
        }

        $this->roles = [Security::ROLE_ANY];

        $user = $this->userService->getAuthenticatedUser();
        if ($user === null) {
            $this->userService->setClientRoles($this->roles);
            return $this->roles;
        }

        $this->roles[] = Security::ROLE_AUTHENTICATED;
        
        $groups = $user->getExternalGroups();
        $this->roles = array_merge($this->roles, $this->mapGroupsToRoles($groups));

        $this->userService->setClientRoles($this->roles);
        
        return $this->roles;
    }

    /**
     * @param Group[] $groups
     * @return array
     */
    private function mapGroupsToRoles(array $groups)
    {
        $requestGroups = explode(',', $this->settingsRoles['submit']);
        $reviewGroups = explode(',', $this->settingsRoles['review']);
        $payGroups = explode(',', $this->settingsRoles['pay']);
        $adminGroups = explode(',', $this->settingsRoles['admin']);

        $roles = [];
        foreach ($groups as $group) {
            $groupName = $group->getName();
            if (in_array($groupName, $requestGroups) && ! in_array(Permission::SUBMIT, $roles)) {
                $roles[] = Permission::SUBMIT;
            }
            if (in_array($groupName, $reviewGroups) && ! in_array(Permission::REVIEW, $roles)) {
                $roles[] = Permission::REVIEW;
            }
            if (in_array($groupName, $payGroups) && ! in_array(Permission::PAY, $roles)) {
                $roles[] = Permission::PAY;
            }
            if (in_array($groupName, $adminGroups) && ! in_array(Permission::ADMIN, $roles)) {
                $roles[] = Permission::ADMIN;
            }
        }
        
        return $roles;
    }
}
