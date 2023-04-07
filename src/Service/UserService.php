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
    private ?User $user = null;

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

    public function hasDivisionRole(int $divisionId, string $role): bool
    {
        if ($this->hasRole(Security::GLOBAL_ADMIN)) {
            return true;
        }

        foreach ($this->getUserPermissions() as $permission) {
            if ($permission->getDivision()->getId() === $divisionId && $permission->getRole() === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $roles
     * @return Division[]
     */
    public function getDivisionsWithRoles(array $roles): array
    {
        $divisions = [];
        foreach ($this->divisionRepository->findBy([]) as $division) {
            if ($this->hasRole(Security::GLOBAL_ADMIN)) {
                $divisions[] = $division;
                continue;
            }
            foreach ($roles as $role) {
                if ($this->hasDivisionRole($division->getId(), $role)) {
                    $divisions[] = $division;
                    continue 2;
                }
            }
        }

        return $divisions;
    }

    /**
     * Returns the logged in user, if available.
     */
    public function getAuthenticatedUser(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }
        
        $userId = $this->session->get('userId');
        if ($userId === null) {
            return null;
        }
        $this->user = $this->userRepository->find($this->session->get('userId'));
        
        return $this->user;
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
     * @throws Exception
     */
    public function syncCharacters(User $user, int $characterId): User
    {
        # TODO Use Account->$id to decide which account needs to be modified: if the character that was used to login
        #      was moved to another external account the current sync modifies the wrong srp user.
        #      Note: this may changed the logged in userId.

        $allKnownCharacterIds = [];
        $mainCharacterId = null;

        $account = $this->provider->getAccount($characterId);

        if ($account) {
            $allKnownCharacterIds = array_map(function (\EveSrp\Provider\Data\Character $character) {
                return $character->getId();
            }, $account->getCharacters());

            // add alts
            foreach ($account->getCharacters() as $character) {
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
        }

        // remove alts, set name of player
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
     * @param int $characterId
     * @param User $user
     * @throws Exception
     */
    public function syncGroups(int $characterId, User $user): void
    {
        $groups = $this->provider->getGroups($characterId);

        // add groups
        foreach ($groups as $groupName) {
            $group = $this->externalGroupRepository->findOneBy(['name' => $groupName]);
            if ($group === null) {
                $group = (new ExternalGroup())->setName($groupName);
                $this->entityManager->persist($group);
            }
            if (! $user->hasExternalGroup($group->getName())) {
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
    }
    
    public function maySee(Request $request): bool
    {
        if ($this->hasRole(Security::GLOBAL_ADMIN)) {
            return true;
        }

        if ($request->getUser()->getId() === $this->getAuthenticatedUser()->getId()) {
            return true;
        }

        $divisionId = $request->getDivision()?->getId();
        if (
            $divisionId &&
            (
                $this->hasDivisionRole($divisionId, Permission::REVIEW) ||
                $this->hasDivisionRole($divisionId, Permission::PAY)
            )
        ) {
            return true;
        }

        return false;
    }
}
