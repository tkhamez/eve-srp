<?php

namespace Test;

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EveSrp\Container;
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
        Action::class,
        Character::class,
        Division::class,
        EsiType::class,
        ExternalGroup::class,
        Modifier::class,
        Permission::class,
        Request::class,
        User::class,
    ];

    /**
     * @throws \Exception
     */
    public static function bootstrap(array $config): void
    {
        // Create DI container
        $builder = new ContainerBuilder();
        $builder->addDefinitions(Container::getDefinition());
        self::$container = $builder->build();
        self::$container->set(Settings::class, new Settings($config));

        // Create database schema
        self::$em = Container::getDefinition()[EntityManagerInterface::class](self::$container);
        $classes = array_map(function (string $className) {
            return self::$em->getClassMetadata($className);
        }, self::$classNames);
        (new SchemaTool(self::$em))->updateSchema($classes);
    }

    public static function emptyDb(): void
    {
        $qb = self::$em->createQueryBuilder();
        foreach (self::$classNames as $className) {
            $qb->delete($className)->getQuery()->execute();
        }
        self::$em->clear();
    }
}
