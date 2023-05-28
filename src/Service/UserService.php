<?php

namespace EveSrp\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eve\Sso\EveAuthentication;
use EveSrp\Exception;
use EveSrp\Model\Character;
use EveSrp\Model\Division;
use EveSrp\Model\ExternalGroup;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Model\User;
use EveSrp\Provider\ProviderInterface;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\ExternalGroupRepository;
use EveSrp\Repository\PermissionRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Security;
use SlimSession\Helper;

class UserService
{
    private ?User $authenticatedUser = null;

    /**
     * @var string[]
     */
    private array $clientRoles = [];

    public function __construct(
        private Helper                  $session,
        private EntityManagerInterface  $entityManager,
        private UserRepository          $userRepository,
        private ExternalGroupRepository $externalGroupRepository,
        private CharacterRepository     $characterRepository,
        private PermissionRepository    $permissionRepository,
        private DivisionRepository      $divisionRepository,
        private ProviderInterface       $provider,
    ) {
    }

    /**
     * Set roles of the current user (authenticated or not).
     *
     * Roles are set by the RoleProvider.
     *
     * @param string[] $roles
     */
    public function setClientRoles(array $roles): void
    {
        $this->clientRoles = $roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->clientRoles);
    }

    /**
     * @return string[] $roles
     */
    public function getRolesForDivision(Division $division): array
    {
        $roles = [];

        foreach ($this->getUserPermissions() as $permission) {
            if ($permission->getDivision()->getId() !== $division->getId()) {
                continue;
            }
            $roles[] = $permission->getRole();
        }

        return $roles;
    }

    /**
     * @param string[] $roles
     */
    public function hasAnyDivisionRole(?Division $division, array $roles): bool
    {
        if (!$division) {
            return false;
        }
        foreach ($this->getUserPermissions() as $permission) {
            foreach ($roles as $role) {
                if ($permission->getDivision()->getId() === $division->getId() && $permission->getRole() === $role) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasDivisionRole(?Division $division, string $role): bool
    {
        return $this->hasAnyDivisionRole($division, [$role]);
    }

    /**
     * @param string[] $roles
     * @return Division[]
     */
    public function getDivisionsWithRoles(array $roles): array
    {
        $divisions = [];
        foreach ($this->divisionRepository->findBy([], ['name' => 'ASC']) as $division) {
            foreach ($roles as $role) {
                if ($this->hasDivisionRole($division, $role)) {
                    $divisions[] = $division;
                    continue 2;
                }
            }
        }
        return $divisions;
    }

    /**
     * Returns the logged-in user, if available.
     */
    public function getAuthenticatedUser(): ?User
    {
        if ($this->authenticatedUser !== null) {
            return $this->authenticatedUser;
        }
        
        $userId = $this->session->get('userId');
        if ($userId === null) {
            return null;
        }
        $this->authenticatedUser = $this->userRepository->find($this->session->get('userId'));
        
        return $this->authenticatedUser;
    }

    /**
     * @return Permission[]
     */
    public function getUserPermissions(): array
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return [];
        }
        
        $groupIds = array_map(function(ExternalGroup $group) {
            return $group->getId();
        }, $user->getExternalGroups());

        return $this->permissionRepository->findBy(['externalGroup' => $groupIds]);
    }

    /**
     * @return User The authenticated user
     */
    public function getUser(EveAuthentication $eveAuth): User
    {
        $characterId = $eveAuth->getCharacterId();

        // get or add new character with user
        $authCharacter = $this->characterRepository->find($characterId);
        if ($authCharacter === null) {
            $user = new User();
            $authCharacter = new Character();
            $authCharacter->setId($characterId);
            $authCharacter->setMain(true);
            $authCharacter->setName($eveAuth->getCharacterName());
            $authCharacter->setUser($user);
            $user->addCharacter($authCharacter);
            $this->entityManager->persist($user);
            $this->entityManager->persist($authCharacter);
        } else {
            $user = $authCharacter->getUser();
            if ($user === null) {
                $user = new User();
                $authCharacter->setUser($user);
                $user->addCharacter($authCharacter);
                $this->entityManager->persist($user);
            }
        }

        // Names can change, so update and persist everything
        $user->setName($authCharacter->getName());
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Syncs EVE alts of logged-in user.
     *
     * @param User $user The logged-in user
     * @param int $characterId EVE ID of the logged-in character.
     * @throws Exception
     */
    public function syncCharacters(User $user, int $characterId): User
    {
        // Get data from provider
        $account = $this->provider->getAccount($characterId);
        if (!$account || empty($account->getId())) {
            return $user;
        }

        // Find correct user
        $externalUser = $this->userRepository->findOneBy(['externalAccountId' => $account->getId()]);
        if (!$externalUser && empty($user->getExternalAccountId())) {
            // First sync for this user, set external ID.
            $user->setExternalAccountId($account->getId());
        } elseif (!$externalUser) {
            // Character was moved to an unknown account
            $newUser = new User();
            $newUser->setName($user->getName());
            $this->entityManager->persist($newUser);
            $user = $newUser;
        } elseif ($externalUser->getId() !== $user->getId()) {
            // Character was moved to a known account
            $user = $externalUser;
        }

        // Variables for last step, set in next step.
        $allKnownCharacterIds = [];
        $mainCharacterId = null;

        // Add alts (create or move if necessary), set name of alts
        foreach ($account->getCharacters() as $character) {
            $allKnownCharacterIds[] = $character->getId();
            $alt = $this->characterRepository->find($character->getId());
            if ($alt === null) {
                $alt = new Character();
                $alt->setId($character->getId());
                $alt->setUser($user);
                $user->addCharacter($alt);
                $this->entityManager->persist($alt);
            } else {
                $oldUser = $alt->getUser();
                if ($oldUser && $oldUser->getId() !== $user->getId()) {
                    $oldUser->removeCharacter($alt);
                }
                $alt->setUser($user);
                $user->addCharacter($alt);
            }
            $alt->setName($character->getName());
            if ($character->getMain()) {
                $mainCharacterId = $character->getId();
            }
        }

        // Remove alts, set main, set name of player
        foreach ($user->getCharacters() as $existingCharacter) {
            if (
                $existingCharacter->getId() !== $characterId &&
                !in_array($existingCharacter->getId(), $allKnownCharacterIds)
            ) {
                $user->removeCharacter($existingCharacter);
                $existingCharacter->setUser();
            }
            if ($existingCharacter->getId() === $mainCharacterId) {
                $existingCharacter->setMain(true);
            } else {
                $existingCharacter->setMain(false);
            }
            if ($existingCharacter->getMain()) {
                $user->setName($existingCharacter->getName());
            }
        }

        // persist
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Syncs external groups of logged in EVE character
     *
     * @throws Exception
     */
    public function syncGroups(User $user): void
    {
        $characterId = isset($user->getCharacters()[0]) ? $user->getCharacters()[0]->getId() : null;
        if (!$characterId) {
            return;
        }

        $groups = $this->provider->getGroups($characterId);

        // add groups
        foreach ($groups as $groupName) {
            $group = $this->externalGroupRepository->findOneBy(['name' => $groupName]);
            if ($group === null) {
                $group = (new ExternalGroup())->setName($groupName);
                $this->entityManager->persist($group);
            }
            if (!$user->hasExternalGroup($group->getName())) {
                $user->addExternalGroup($group);
            }
        }

        // remove groups
        foreach ($user->getExternalGroups() as $externalGroup) {
            if (!in_array($externalGroup->getName(), $groups)) {
                $user->removeExternalGroup($externalGroup);
            }
        }
        
        // persist
        $this->entityManager->flush();

        $this->session->set('lastGroupSync', time());
    }
    
    public function maySeeRequest(Request $request): bool
    {
        if (
            $this->hasRole(Security::GLOBAL_ADMIN) ||
            $request->getUser()->getId() === $this->getAuthenticatedUser()->getId() ||
            $this->hasAnyDivisionRole($request->getDivision(), [Permission::REVIEW, Permission::PAY])
        ) {
            return true;
        }

        return false;
    }

    public function getMain(int $characterId): ?Character
    {
        $character = $this->characterRepository->find($characterId);
        foreach ($character?->getUser()?->getCharacters() ?? [] as $char) {
            if ($char->getMain()) {
                return $char;
            }
        }
        return null;
    }
}
