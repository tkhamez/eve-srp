<?php

declare(strict_types=1);

use Brave\EveSrp\Bootstrap;

require_once(__DIR__ . '/../vendor/autoload.php');

define('ROOT_DIR', realpath(__DIR__ . '/../'));

$bootstrap = new Bootstrap();
$app = $bootstrap->enableRoutes();
$app->run();
