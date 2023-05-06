<?php

// Configuration file for Doctrine migrations

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Bootstrap;

define('ROOT_DIR', realpath(__DIR__ . '/../'));

require ROOT_DIR . '/vendor/autoload.php';

/* @var EntityManagerInterface $entityManager */
$entityManager = (new Bootstrap())->getContainer()->get(EntityManagerInterface::class);

$migrationsPaths = [];
if ($entityManager->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
    $migrationsPaths['EveSrp\Migrations\MySQL'] = 'src/Migrations/MySQL';
} elseif ($entityManager->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
    $migrationsPaths['EveSrp\Migrations\PostgreSQL'] = 'src/Migrations/PostgreSQL';
}

$config = new ConfigurationArray([
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
    'migrations_paths' => $migrationsPaths,
    'all_or_nothing' => true,
    'transactional' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
    'connection' => null,
    'em' => null,
]);

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($entityManager));
