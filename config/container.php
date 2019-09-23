<?php

declare(strict_types=1);

use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\Request;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\RoleProviderInterface;
use Brave\EveSrp\Repository\CharacterRepository;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\Repository\UserRepository;
use Brave\EveSrp\SessionHandler;
use Brave\EveSrp\TwigData;
use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\Sso\Basics\AuthenticationProvider;
use Brave\Sso\Basics\SessionHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

return [
    'settings' => require_once('config.php'),

    App::class => function (ContainerInterface $container)
    {
        AppFactory::setContainer($container);
        return AppFactory::create();
    },

    ResponseFactoryInterface::class => function ()
    {
        return new ResponseFactory();
    },

    GenericProvider::class => function (ContainerInterface $container)
    {
        $settings = $container->get('settings');

        return new GenericProvider([
            'clientId' => $settings['SSO_CLIENT_ID'],
            'clientSecret' => $settings['SSO_CLIENT_SECRET'],
            'redirectUri' => $settings['SSO_REDIRECT_URI'],
            'urlAuthorize' => $settings['SSO_URL_AUTHORIZE'],
            'urlAccessToken' => $settings['SSO_URL_ACCESS_TOKEN'],
            'urlResourceOwnerDetails' => '',
        ]);
    },

    AuthenticationProvider::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');

        return new AuthenticationProvider(
            $container->get(GenericProvider::class),
            [],
            $settings['SSO_URL_JWT_KEY_SET']
        );
    },

    SessionHandler::class => function (ContainerInterface $container)
    {
        return new SessionHandler($container);
    },

    SessionHandlerInterface::class => function (ContainerInterface $container)
    {
        return $container->get(SessionHandler::class);
    },

    ApplicationApi::class => function (ContainerInterface $container)
    {
        $apiKey = base64_encode(
            $container->get('settings')['CORE_APP_ID'] .
            ':'.
            $container->get('settings')['CORE_APP_TOKEN']
        );
        $config = Brave\NeucoreApi\Configuration::getDefaultConfiguration();
        $config->setHost($container->get('settings')['CORE_URL']);
        $config->setAccessToken($apiKey);
        return new ApplicationApi(null, $config);
    },

    RoleProviderInterface::class => function (ContainerInterface $container)
    {
        $class = $container->get('settings')['ROLE_PROVIDER'];
        return new $class($container);
    },

    CharacterProviderInterface::class => function (ContainerInterface $container)
    {
        $class = $container->get('settings')['CHAR_PROVIDER'];
        return new $class($container);
    },
    
    Environment::class => function (ContainerInterface $container)
    {
        $options = [];
        if ($container->get('settings')['APP_ENV'] === 'dev') {
            $options['debug'] = true;
        } else {
            $options['cache'] = ROOT_DIR . '/cache/compilation_cache';
        }
        $loader = new FilesystemLoader(ROOT_DIR . '/templates');
        $twig = new Environment($loader, $options);
        if ($container->get('settings')['APP_ENV'] === 'dev') {
            $twig->addExtension(new DebugExtension());
        }
        $twig->addGlobal('data', new TwigData($container));

        return $twig;
    },

    EntityManagerInterface::class => function (ContainerInterface $container)
    {
        return EntityManager::create(
            ['url' => $container->get('settings')['DB_URL']],
            Setup::createAnnotationMetadataConfiguration(
                [ROOT_DIR . '/src/Model'],
                true,
                null,
                null,
                false
            )
        );
    },

    CharacterRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        $class = $em->getMetadataFactory()->getMetadataFor(Character::class);
        return new CharacterRepository($em, $class);
    },

    RequestRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        $class = $em->getMetadataFactory()->getMetadataFor(Request::class);
        return new RequestRepository($em, $class);
    },

    UserRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        $class = $em->getMetadataFactory()->getMetadataFor(User::class);
        return new UserRepository($em, $class);
    },
];
