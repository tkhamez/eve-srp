<?php

declare(strict_types=1);

namespace EveSrp;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use EveSrp\Slim\ErrorHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
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
     * @throws \Exception
     */
    public function __construct()
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        if (is_readable(ROOT_DIR . '/.env')) {
            $dotEnv = Dotenv::createImmutable(ROOT_DIR);
            $dotEnv->load();
        }

        $builder = new ContainerBuilder();
        $builder->addDefinitions(include ROOT_DIR . '/config/container.php');
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
        } catch (\Exception $e) {
            error_log('Bootstrap::run(): ' . $e->getMessage());
        }

        try {
            $app->run();
        } catch (Throwable $e) {
            error_log((string) $e);
            if ($e instanceof HttpNotFoundException) {
                $msg = 'Not found';
            } else {
                $msg = 'Error 500';
            }
            echo "<body style='background-color: black; color: white;'>
                    <h1>$msg</h1>
                    <a style='color: white;' href='/'>Home</a>
                </body>";
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
        $app->add(new RoleMiddleware($this->container->get(RoleProviderInterface::class)));

        // Add routing middleware after SecureRouteMiddleware and RoleMiddleware because they depend on the route.
        $app->addRoutingMiddleware();

        $app->add(new Session([
            'name' => 'eve_srp_session',
            'httponly' => true,
            'autorefresh' => true,
        ]));

        $errorMiddleware = $app->addErrorMiddleware(false, true, true);
        $errorMiddleware->setDefaultErrorHandler(new ErrorHandler(
            $app->getCallableResolver(),
            $app->getResponseFactory()
        ));
    }
}
