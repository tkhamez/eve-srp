<?php

namespace Test;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EveSrp\Bootstrap;
use EveSrp\Model\Action;
use EveSrp\Model\Character;
use EveSrp\Model\Division;
use EveSrp\Model\EsiType;
use EveSrp\Model\ExternalGroup;
use EveSrp\Model\Modifier;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Model\User;
use EveSrp\Settings;
use Psr\Container\ContainerInterface;

class TestHelper
{
    public static ContainerInterface $container;

    private static EntityManagerInterface $em;

    private static array $classNames = [
        EsiType::class,
        Action::class,
        Modifier::class,
        Request::class,
        Permission::class,
        Division::class,
        Character::class,
        User::class,
        ExternalGroup::class,
    ];

    /**
     * @throws \Throwable
     */
    public static function bootstrap(): void
    {
        // Create DI container
        self::$container = (new Bootstrap())->getContainer();

        $config = array_merge(
            require_once ROOT_DIR . '/config/config.php',
            ['EVE_SRP_ENV' => 'dev', 'DB_URL' => $_ENV['EVE_SRP_DB_TEST_URL']]
        );
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        self::$container->set(Settings::class, new Settings($config));

        // Create database schema
        self::$em = self::$container->get(EntityManagerInterface::class);
        $classes = array_map(function (string $className) {
            return self::$em->getClassMetadata($className);
        }, self::$classNames);
        (new SchemaTool(self::$em))->updateSchema($classes);
    }

    public static function emptyDb(): void
    {
        $qb = self::$em->createQueryBuilder();
        foreach (self::$classNames as $className) {
            $qb->delete($className, 'c')->getQuery()->execute();
        }
        self::$em->clear();
    }
}
