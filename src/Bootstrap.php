<?php

declare(strict_types=1);

namespace EveSrp;

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use EveSrp\Misc\SlimErrorHandler;
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
    private ContainerInterface $container;

    /**
     * Bootstrap constructor
     * @throws \Exception
     */
    public function __construct()
    {
        date_default_timezone_set('UTC');
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', ROOT_DIR . '/storage/error-' . date('Ym') . '.log');
        error_reporting(E_ALL);

        if (is_readable(ROOT_DIR . '/config/.env')) {
            $dotEnv = Dotenv::createImmutable(ROOT_DIR . '/config');
            $dotEnv->load();
        } elseif (empty($_ENV)) {
            $_ENV = getenv();
        }

        $builder = new ContainerBuilder();
        $builder->addDefinitions(Container::getDefinition());
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
        } catch (\Throwable $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
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
        
        $routes = require_once ROOT_DIR . '/config/routes.php';
        foreach ($routes as $route) {
            if ($route[0] === 'get') {
                $app->get($route[1],  $route[2]);
            } elseif ($route[0] === 'post') {
                $app->post($route[1],  $route[2]);
            }
        }

        return $app;
    }

    /**
     * @throws \Throwable
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
        $errorMiddleware->setDefaultErrorHandler(new SlimErrorHandler(
            $app->getCallableResolver(),
            $app->getResponseFactory()
        ));
    }
}
