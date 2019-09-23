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
        $this->characterProvider->clear();

        $this->initCharacter($this->sessionHandler->get('eveAuth'), $request);
        
        return $response->withHeader('Location', '/');
    }

    /** @noinspection PhpUnused */
    public function logout(
        /** @noinspection PhpUnusedParameterInspection */ ServerRequestInterface $request, 
                                                          ResponseInterface $response
    ) {
        $this->sessionHandler->set('eveAuth', null);
        $this->roleProvider->clear();
        $this->characterProvider->clear();
        
        return $response->withHeader('Location', '/');
    }
    
    private function initCharacter(EveAuthentication $eveAuth, ServerRequestInterface $request)
    {
        // get or add new character with user
        $character = $this->characterRepository->find($eveAuth->getCharacterId());
        if ($character === null) {
            $user = new User();
            $character = new Character();
            $character->setId($eveAuth->getCharacterId());
            $character->setUser($user);
            $this->entityManager->persist($user);
            $this->entityManager->persist($character);
        } else {
            $user = $character->getUser();
        }
        
        // add alts
        foreach ($this->characterProvider->getCharacters($request) as $altId) {
            if ((int) $altId === $character->getId()) {
                continue;
            }
            $alt = $this->characterRepository->find($altId);
            if ($alt === null) {
                $alt = new Character();
                $alt->setId($altId);
                $alt->setUser($user);
                $this->entityManager->persist($alt);
            } else {
                $alt->setUser($user);
            }
        }
        
        // persist
        $this->entityManager->flush();
    }
}
