<?php

declare(strict_types=1);

namespace EveSrp\Provider;

use EveSrp\Model\User;
use EveSrp\Security;
use EveSrp\Service\UserService;
use EveSrp\Settings;
use Psr\Http\Message\ServerRequestInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Maps external groups to roles for an authenticated user.
 */
class RoleProvider implements RoleProviderInterface
{
    private UserService $userService;

    /**
     * @var string[] 
     */
    private array $adminGroups = [];

    private array $roles = [];
    
    public function __construct(UserService $userService, Settings $settings)
    {
        $this->userService = $userService;
        if ($settings['ROLE_GLOBAL_ADMIN'] !== '') {
            $this->adminGroups = explode(',', $settings['ROLE_GLOBAL_ADMIN']);
        }
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
        
        $this->roles = array_merge($this->roles, $this->mapGroupsToRoles($user));

        $this->userService->setClientRoles($this->roles);

        return $this->roles;
    }

    private function mapGroupsToRoles(User $user): array
    {
        $roles = [];
        
        // division roles
        foreach ($this->userService->getUserPermissions() as $permission) {
            if (! in_array($permission->getRole(), $roles)) {
                $roles[] = $permission->getRole();
            }
        }
        
        // global admin role
        foreach ($user->getExternalGroups() as $group) {
            if (in_array($group->getName(), $this->adminGroups) && ! in_array(Security::GLOBAL_ADMIN, $roles)) {
                $roles[] = Security::GLOBAL_ADMIN;
            }
        }
        
        return $roles;
    }
}
