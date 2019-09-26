<?php

declare(strict_types=1);

namespace Brave\EveSrp;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\Session;
use Throwable;
use Tkhamez\Slim\RoleAuth\RoleMiddleware;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;
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

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function run(): void
    {
        $app = $this->enableRoutes();
        
        try {
            $this->addMiddleware($app);
        } catch (Exception $e) {
            error_log('Bootstrap::run(): ' . $e->getMessage());
        }

        try {
            $app->run();
        } catch (Throwable $e) {
            error_log((string) $e);
        }
    }

    private function enableRoutes(): App
    {
        AppFactory::setContainer($this->container);
        $app = AppFactory::create();
        
        $routesConfigurator = require_once(ROOT_DIR . '/config/routes.php');
        $routesConfigurator($app);

        return $app;
    }

    /**
     * @param App $app
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function addMiddleware(App $app): void
    {
        $app->add(new SecureRouteMiddleware(
            $this->container->get(ResponseFactoryInterface::class),
            include ROOT_DIR . '/config/security.php',
            ['redirect_url' => '/login']
        ));

        // Add routing middleware after SecureRouteMiddleware,
        // so the `route` attribute from `$request` is available
        $app->addRoutingMiddleware();
        
        $app->add(new RoleMiddleware($this->container->get(RoleProviderInterface::class)));
        $app->add(new Session([
            'name' => 'brave_service',
            'autorefresh' => true,
            'lifetime' => '1 hour'
        ]));
    }
}
