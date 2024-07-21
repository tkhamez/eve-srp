<?php

// Configuration file for Doctrine migrations

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\YamlFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Bootstrap;

define('ROOT_DIR', realpath(__DIR__ . '/../'));

require ROOT_DIR . '/vendor/autoload.php';

/* @var EntityManagerInterface $entityManager */
$entityManager = (new Bootstrap())->getContainer()->get(EntityManagerInterface::class);

$configFile = null;
$platform = $entityManager->getConnection()->getDatabasePlatform();
if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
    $configFile = 'config/migrations-mysql.yml';
} elseif ($platform instanceof PostgreSQLPlatform) {
    $configFile = 'config/migrations-pgsql.yml';
}

if ($configFile) {
    return DependencyFactory::fromEntityManager(
        new YamlFile($configFile),
        new ExistingEntityManager($entityManager)
    );
}
