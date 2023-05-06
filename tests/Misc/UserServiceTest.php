<?php

namespace Test\Misc;

use Doctrine\ORM\EntityManagerInterface;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SlimSession\Helper;
use Test\TestHelper;
use Test\TestProvider;

class UserServiceTest extends TestCase
{
    private UserService $userService;

    private EntityManagerInterface $em;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        TestHelper::emptyDb();

        $this->em = TestHelper::$container->get(EntityManagerInterface::class);
        $this->userService = new UserService(
            new Helper(),
            $this->em,
            TestHelper::$container->get(UserRepository::class),
            TestHelper::$container->get(ExternalGroupRepository::class),
            TestHelper::$container->get(CharacterRepository::class),
            TestHelper::$container->get(PermissionRepository::class),
            TestHelper::$container->get(DivisionRepository::class),
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
