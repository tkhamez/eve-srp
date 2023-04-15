<?php

namespace Test\Misc;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Container;
use EveSrp\Exception;
use EveSrp\Model\Character;
use EveSrp\Model\User;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\ExternalGroupRepository;
use EveSrp\Repository\PermissionRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Service\UserService;
use PHPUnit\Framework\TestCase;
use SlimSession\Helper;
use Test\TestHelper;
use Test\TestProvider;

class UserServiceTest extends TestCase
{
    private UserService $userService;

    private EntityManagerInterface $em;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        TestHelper::emptyDb();

        $this->em = Container::getDefinition()[EntityManagerInterface::class](TestHelper::$container);
        $this->userService = new UserService(
            new Helper(),
            $this->em,
            Container::getDefinition()[UserRepository::class](TestHelper::$container),
            Container::getDefinition()[ExternalGroupRepository::class](TestHelper::$container),
            Container::getDefinition()[CharacterRepository::class](TestHelper::$container),
            Container::getDefinition()[PermissionRepository::class](TestHelper::$container),
            Container::getDefinition()[DivisionRepository::class](TestHelper::$container),
            new TestProvider(),
        );
    }

    /**
     * @throws Exception
     */
    public function testSyncCharacters()
    {
        $characterId = 100200300;
        $character = (new Character())->setId($characterId);
        $user = (new User())->addCharacter($character);
        $character->setUser($user);
        $this->em->persist($character);
        $this->em->persist($user);
        $this->em->flush();

        # TODO write proper tests

        $actual = $this->userService->syncCharacters($user, $characterId);

        $this->assertSame($actual->getId(), $user->getId());
    }
}
