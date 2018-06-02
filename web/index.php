<?php
require_once(__DIR__ . '/../vendor/autoload.php');

define('ROOT_DIR', realpath(__DIR__ . '/../'));

$bootstrap = new \Brave\CoreConnector\Bootstrap();
$app = $bootstrap->enableRoutes();
$app->run();
