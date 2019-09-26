<?php

declare(strict_types=1);

use Brave\EveSrp\Model\Action;
use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\Division;
use Brave\EveSrp\Model\ExternalGroup;
use Brave\EveSrp\Model\Request;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\Provider\CharacterProviderInterface;
use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\Provider\RoleProvider;
use Brave\EveSrp\Repository\ActionRepository;
use Brave\EveSrp\Repository\CharacterRepository;
use Brave\EveSrp\Repository\DivisionRepository;
use Brave\EveSrp\Repository\ExternalGroupRepository;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\Repository\UserRepository;
use Brave\EveSrp\SessionHandler;
use Brave\EveSrp\Twig\Extension;
use Brave\EveSrp\Twig\GlobalData;
use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\Sso\Basics\AuthenticationProvider;
use Brave\Sso\Basics\SessionHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

return [
    'settings' => require_once('config.php'),

    // Slim
    ResponseFactoryInterface::class => function ()
    {
        return new ResponseFactory();
    },
    SessionHandlerInterface::class => function (ContainerInterface $container)
    {
        return $container->get(SessionHandler::class);
    },

    // EVE-SRP
    RoleProviderInterface::class => function (ContainerInterface $container) 
    {
        return $container->get(RoleProvider::class);
    },
    
    // Pluggable adapter
    GroupProviderInterface::class => function (ContainerInterface $container)
    {
        $class = $container->get('settings')['GROUP_PROVIDER'];
        return new $class($container);
    },
    CharacterProviderInterface::class => function (ContainerInterface $container)
    {
        $class = $container->get('settings')['CHARACTER_PROVIDER'];
        return new $class($container);
    },

    // Guzzle HTTP client
    ClientInterface::class => function (ContainerInterface $container)
    {
        return new Client([
            'headers' => ['User-Agent' => $container->get('settings')['HTTP']['user_agent']]
        ]);
    },

    // SSO
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
        ], [
            'httpClient' => $container->get(ClientInterface::class)
        ]);
    },
    AuthenticationProvider::class => function (ContainerInterface $container)
    {
        $settings = $container->get('settings');
        return new AuthenticationProvider(
            $container->get(GenericProvider::class),
            [],
            $settings['SSO_URL_JWT_KEY_SET']
        );
    },

    // Neucore API
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
        return new ApplicationApi($container->get(ClientInterface::class), $config);
    },

    // Twig
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
        $twig->addGlobal('data', new GlobalData($container));
        $twig->addExtension(new Extension($container));
        return $twig;
    },

    // Doctrine ORM
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
    ActionRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new ActionRepository($em, $em->getClassMetadata(Action::class));
    },
    CharacterRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new CharacterRepository($em, $em->getClassMetadata(Character::class));
    },
    DivisionRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new DivisionRepository($em, $em->getClassMetadata(Division::class));
    },
    RequestRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new RequestRepository($em, $em->getClassMetadata(Request::class));
    },
    UserRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new UserRepository($em, $em->getClassMetadata(User::class));
    },
    ExternalGroupRepository::class => function (ContainerInterface $container)
    {
        $em = $container->get(EntityManagerInterface::class);
        return new ExternalGroupRepository($em, $em->getClassMetadata(ExternalGroup::class));
    },
];
