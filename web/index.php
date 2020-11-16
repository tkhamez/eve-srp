<?php

declare(strict_types=1);

use EveSrp\Bootstrap;

// For the built-in PHP dev server, check for requests to be served as static files
if (PHP_SAPI == 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    if (is_file(__DIR__ . $url['path'])) {
        return false;
    }
}

require_once(__DIR__ . '/../vendor/autoload.php');

define('ROOT_DIR', realpath(__DIR__ . '/../'));

(new Bootstrap())->run();
