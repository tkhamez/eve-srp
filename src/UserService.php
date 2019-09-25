<?php

namespace Brave\EveSrp;

use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Repository\CharacterRepository;
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
     * @var CharacterRepository
     */
    private $characterRepository;

    /**
     * @var CharacterProviderInterface
     */
    private $characterProvider;

    public function __construct(ContainerInterface $container) {
        $this->session = $container->get(SessionHandlerInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->characterRepository = $container->get(CharacterRepository::class);
        $this->characterProvider = $container->get(CharacterProviderInterface::class);
    }

    /**
     * Returns the logged in user, if available.
     */
    public function getUser(): ?User
    {
        return $this->userRepository->find($this->session->get('userId'));
    }

    public function syncCharacters(EveAuthentication $eveAuth): User
    {
        // get or add new character with user
        $authCharacter = $this->characterRepository->find($eveAuth->getCharacterId());
        if ($authCharacter === null) {
            $user = new User();
            $authCharacter = new Character();
            $authCharacter->setId($eveAuth->getCharacterId());
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
        $allCharacterIds = $this->characterProvider->getCharacters();
        foreach ($allCharacterIds as $altId) {
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

        // remove alts, set name of player - but only if the character is known
        if (count($allCharacterIds) > 0) {
            $mainCharacterId = $this->characterProvider->getMain();
            foreach ($user->getCharacters() as $existingCharacter) {
                if (! in_array($existingCharacter->getId(), $allCharacterIds)) {
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
        }

        // persist
        $this->entityManager->flush();

        return $user;
    }
}
