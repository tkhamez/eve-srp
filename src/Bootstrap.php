<?php

declare(strict_types=1);

namespace Brave\EveSrp;

use Brave\EveSrp\Middleware\CharacterMiddleware;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\RoleProviderInterface;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Middleware\Session;
use Tkhamez\Slim\RoleAuth\RoleMiddleware;
use Tkhamez\Slim\RoleAuth\SecureRouteMiddleware;

class Bootstrap
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Bootstrap constructor
     * @throws Exception
     */
    public function __construct()
    {
        if (is_readable(ROOT_DIR . '/.env')) {
            $dotEnv = new Dotenv(ROOT_DIR);
            $dotEnv->load();
        }

        $builder = new ContainerBuilder();
        $builder->addDefinitions(require_once(ROOT_DIR . '/config/container.php'));
        $this->container = $builder->build();
    }

    /**
     * @return App
     * @throws ContainerExceptionInterface
     */
    public function enableRoutes()
    {
        /** @var App $app */
        $routesConfigurator = require_once(ROOT_DIR . '/config/routes.php');
        $app = $routesConfigurator($this->container);

        $app->add(new SecureRouteMiddleware(
            $this->container->get(ResponseFactoryInterface::class), 
            include ROOT_DIR . '/config/security.php',
            ['redirect_url' => '/login']
        ));
        $app->add(new RoleMiddleware($this->container->get(RoleProviderInterface::class)));
        $app->add(new CharacterMiddleware($this->container->get(CharacterProviderInterface::class)));
        
        $app->add(new Session([
            'name' => 'brave_service',
            'autorefresh' => true,
            'lifetime' => '1 hour'
        ]));

        // Add routing middleware last, so the `route` attribute from `$request` is available
        $app->addRoutingMiddleware();
        
        return $app;
    }
}
