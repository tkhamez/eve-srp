<?php

declare(strict_types=1);

use EveSrp\Bootstrap;

require_once(__DIR__ . '/../vendor/autoload.php');

define('ROOT_DIR', realpath(__DIR__ . '/../'));

(new Bootstrap())->run();
