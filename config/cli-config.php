<?php
// Configuration file for Doctrine migrations

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Bootstrap;

define('ROOT_DIR', realpath(__DIR__ . '/../'));

require ROOT_DIR . '/vendor/autoload.php';

$config = new PhpFile('config/migrations.php');
/** @noinspection PhpUnhandledExceptionInspection */
$entityManager = (new Bootstrap())->getContainer()->get(EntityManagerInterface::class);

return DependencyFactory::fromEntityManager($config, new ExistingEntityManager($entityManager));
