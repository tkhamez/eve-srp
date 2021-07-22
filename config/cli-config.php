<?php
/**
 * Configuration for vendor/bin/doctrine.
 */

declare(strict_types=1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use EveSrp\Bootstrap;
use Doctrine\ORM\EntityManagerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

define('ROOT_DIR', realpath(__DIR__ . '/../'));

return ConsoleRunner::createHelperSet(
    (new Bootstrap())->getContainer()->get(EntityManagerInterface::class)
);
