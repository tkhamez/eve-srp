<?php

namespace Brave\EveSrp;

use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\ExternalGroup;
use Brave\EveSrp\Model\Request;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\Repository\CharacterRepository;
use Brave\EveSrp\Repository\ExternalGroupRepository;
use Brave\EveSrp\Repository\PermissionRepository;
use Brave\EveSrp\Repository\UserRepository;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

class UserService
{
    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ExternalGroupRepository
     */
    private $externalGroupRepository;

    /**
     * @var CharacterRepository
     */
    private $characterRepository;

    /**
     * @var PermissionRepository
     */
    private $permissionRepository;

    /**
     * @var CharacterProviderInterface
     */
    private $characterProvider;

    /**
     * @var GroupProviderInterface
     */
    private $groupProvider;

    /**
     * @var User|null
     */
    private $user;

    /**
     * @var string[]
     */
    private $clientRoles = [];

    public function __construct(ContainerInterface $container) {
        $this->session = $container->get(SessionHandlerInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->externalGroupRepository = $container->get(ExternalGroupRepository::class);
        $this->characterRepository = $container->get(CharacterRepository::class);
        $this->permissionRepository = $container->get(PermissionRepository::class);
        $this->characterProvider = $container->get(CharacterProviderInterface::class);
        $this->groupProvider = $container->get(GroupProviderInterface::class);
    }

    /**
     * @param string[] $roles
     */
    public function setClientRoles(array $roles): void
    {
        $this->clientRoles = $roles;
    }

    /**
     * Return roles of the current user (authenticated or not).
     *
     * Roles are set by the RoleProvider.
     *
     * @return string[]
     */
    public function getClientRoles(): array
    {
        return $this->clientRoles;
    }

    public function hasRole(string $role)
    {
        return in_array($role, $this->clientRoles);
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
     * Syncs EVE alts of logged in EVE character and returns the corresponding user.
     * 
     * @param EveAuthentication $eveAuth
     * @return User The logged in user.
     */
    public function syncCharacters(EveAuthentication $eveAuth): User
    {
        $characterId = $eveAuth->getCharacterId();
        
        // get or add new character with user
        $authCharacter = $this->characterRepository->find($characterId);
        if ($authCharacter === null) {
            $user = new User();
            $authCharacter = new Character();
            $authCharacter->setId($characterId);
            $authCharacter->setMain(true);
            $authCharacter->setUser($user);
            $authCharacter->setName($eveAuth->getCharacterName());
            $user->addCharacter($authCharacter);
            $user->setName($authCharacter->getName());
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

        // add alts
        $allKnownCharacterIds = $this->characterProvider->getCharacters($characterId);
        foreach ($allKnownCharacterIds as $altId) {
            $alt = $this->characterRepository->find($altId);
            if ($alt === null) {
                $alt = new Character();
                $alt->setId($altId);
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
            $alt->setName($this->characterProvider->getName($alt->getId()));
        }

        // remove alts, set name of player
        $mainCharacterId = $this->characterProvider->getMain($characterId);
        foreach ($user->getCharacters() as $existingCharacter) {
            if ($existingCharacter->getId() !== $characterId
                && ! in_array($existingCharacter->getId(), $allKnownCharacterIds)
            ) {
                $user->removeCharacter($existingCharacter);
                $existingCharacter->setUser(null);
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
     */
    public function syncGroups(int $characterId, User $user): void
    {
        $groups = $this->groupProvider->getGroups($characterId);
        
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
            if (! in_array($externalGroup->getName(), $groups)) {
                $user->removeExternalGroup($externalGroup);
            }
        }
        
        // persist
        $this->entityManager->flush();
    }
    
    public function maySee(Request $request): bool
    {
        # TODO implement divisions
        
        if ($this->hasRole(Model\Permission::REVIEW) || $this->hasRole(Model\Permission::PAY)) {
            return true;
        }
        if ($request->getSubmitter()->getId() === $this->getAuthenticatedUser()->getId()) {
            return true;
        }
        return false;
    }
}
