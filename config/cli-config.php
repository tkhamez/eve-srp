<?php
/**
 * Configuration for vendor/bin/doctrine.
 */

declare(strict_types=1);

use EveSrp\Bootstrap;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

require_once(__DIR__ . '/../vendor/autoload.php');

define('ROOT_DIR', realpath(__DIR__ . '/../'));

$em = (new Bootstrap())->getContainer()->get(EntityManagerInterface::class);
$helpers = new HelperSet(array(
    'db' => new ConnectionHelper($em->getConnection()),
    'em' => new EntityManagerHelper($em)
));
