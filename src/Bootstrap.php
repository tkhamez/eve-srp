<?php
namespace Brave\CoreConnector;

use Psr\Container\ContainerInterface;

/**
 *
 */
class Bootstrap
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Bootstrap constructor
     */
    public function __construct()
    {
        $container = new \Slim\Container(require_once(ROOT_DIR . '/config/container.php'));
        $this->container = $container;

    }

    /**
     * @return \Slim\App
     */
    public function enableRoutes()
    {
        $routesConfigurator = require_once(ROOT_DIR . '/config/routes.php');
        $app = $routesConfigurator($this->container);

        $app->add(new \Slim\Middleware\Session([
            'name' => 'brave_service',
            'autorefresh' => true,
            'lifetime' => '1 hour'
        ]));

        return $app;
    }
}
