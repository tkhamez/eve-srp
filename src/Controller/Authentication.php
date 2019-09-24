<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\RoleProviderInterface;
use Brave\EveSrp\Repository\CharacterRepository;
use Brave\Sso\Basics\AuthenticationController;
use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Authentication extends AuthenticationController
{
    /**
     * @var RoleProviderInterface
     */
    private $roleProvider;

    /**
     * @var CharacterProviderInterface
     */
    private $characterProvider;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    
    /**
     * @var CharacterRepository
     */
    private $characterRepository;

    /**
     * @var string
     */
    protected $template = ROOT_DIR . '/templates/login.html';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        
        $this->roleProvider = $container->get(RoleProviderInterface::class);
        $this->characterProvider = $container->get(CharacterProviderInterface::class);
        $this->sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->characterRepository = $container->get(CharacterRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    /**
     * EVE SSO callback.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param bool $ssoV2
     * @return ResponseInterface
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response, $ssoV2 = false)
    {
        try {
            $response = parent::auth($request, $response, true);
        } catch (Exception $e) {
            error_log('Authentication::auth: ' . $e->getMessage());
        }
        $this->roleProvider->clear();

        $this->syncCharacters($this->sessionHandler->get('eveAuth'));
        
        return $response->withHeader('Location', '/');
        #return $response;
    }

    /** @noinspection PhpUnused */
    public function logout(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response
    ) {
        $this->sessionHandler->set('eveAuth', null);
        $this->roleProvider->clear();
        
        return $response->withHeader('Location', '/');
    }
    
    private function syncCharacters(EveAuthentication $eveAuth)
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
                    $existingCharacter->setUser(null); // TODO delete char instead and make user not null?
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
    }
}
