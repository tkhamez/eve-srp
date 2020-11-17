<?php

declare(strict_types=1);

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\Api\ApplicationCharactersApi;
use Brave\NeucoreApi\Api\ApplicationGroupsApi;
use Brave\NeucoreApi\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Eve\Sso\AuthenticationProvider;
use EveSrp\Model\Action;
use EveSrp\Model\Character;
use EveSrp\Model\Division;
use EveSrp\Model\EsiType;
use EveSrp\Model\ExternalGroup;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Model\User;
use EveSrp\Provider\InterfaceCharacterProvider;
use EveSrp\Provider\InterfaceGroupProvider;
use EveSrp\Provider\RoleProvider;
use EveSrp\Repository\ActionRepository;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\EsiTypeRepository;
use EveSrp\Repository\ExternalGroupRepository;
use EveSrp\Repository\PermissionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Service\UserService;
use EveSrp\Twig\Extension;
use EveSrp\Twig\GlobalData;
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
    ResponseFactoryInterface::class => function () {
        return new ResponseFactory();
    },

    // EVE-SRP
    RoleProviderInterface::class => function (ContainerInterface $container) {
        return $container->get(RoleProvider::class);
    },
    RoleProvider::class => function (ContainerInterface $container) {
        return new RoleProvider($container);
    },
    UserService::class => function (ContainerInterface $container) {
        return new UserService($container);
    },
    
    // Pluggable adapter
    InterfaceGroupProvider::class => function (ContainerInterface $container) {
        $class = $container->get('settings')['GROUP_PROVIDER'];
        return new $class($container);
    },
    InterfaceCharacterProvider::class => function (ContainerInterface $container) {
        $class = $container->get('settings')['CHARACTER_PROVIDER'];
        return new $class($container);
    },

    // Guzzle HTTP client
    ClientInterface::class => function (ContainerInterface $container) {
        return new Client([
            'headers' => ['User-Agent' => $container->get('settings')['HTTP_USER_AGENT']]
        ]);
    },

    // SSO
    GenericProvider::class => function (ContainerInterface $container) {
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
    AuthenticationProvider::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        return new AuthenticationProvider(
            $container->get(GenericProvider::class),
            [],
            $settings['SSO_URL_JWT_KEY_SET']
        );
    },

    // Neucore API
    Configuration::class => function (ContainerInterface $container) {
        $apiKey = base64_encode(
            $container->get('settings')['NEUCORE_APP_ID'] .
            ':' .
            $container->get('settings')['NEUCORE_APP_TOKEN']
        );
        $config = Configuration::getDefaultConfiguration();
        $config->setHost($container->get('settings')['NEUCORE_DOMAIN'].'/api');
        $config->setAccessToken($apiKey);
        return $config;
    },
    ApplicationApi::class => function (ContainerInterface $container) {
        return new ApplicationApi(
            $container->get(ClientInterface::class),
            $container->get(Configuration::class)
        );
    },
    ApplicationCharactersApi::class => function (ContainerInterface $container) {
        return new ApplicationCharactersApi(
            $container->get(ClientInterface::class),
            $container->get(Configuration::class)
        );
    },
    ApplicationGroupsApi::class => function (ContainerInterface $container) {
        return new ApplicationGroupsApi(
            $container->get(ClientInterface::class),
            $container->get(Configuration::class)
        );
    },

    // Twig
    Environment::class => function (ContainerInterface $container) {
        $options = [];
        if ($container->get('settings')['APP_ENV'] === 'dev') {
            $options['debug'] = true;
        } else {
            $options['cache'] = ROOT_DIR . '/cache/compilation_cache';
        }
        $loader = new FilesystemLoader(ROOT_DIR . '/templates');
        $loader->addPath(ROOT_DIR . '/web/dist');
        $twig = new Environment($loader, $options);
        if ($container->get('settings')['APP_ENV'] === 'dev') {
            $twig->addExtension(new DebugExtension());
        }
        $twig->addGlobal('data', new GlobalData($container));
        $twig->addExtension(new Extension($container));
        return $twig;
    },

    // Doctrine ORM
    EntityManagerInterface::class => function (ContainerInterface $container) {
        return EntityManager::create(
            ['url' => $container->get('settings')['DB_URL']],
            Setup::createAnnotationMetadataConfiguration([ROOT_DIR . '/src/Model'], true,  null, null, false)
        );
    },
    ActionRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new ActionRepository($em, $em->getClassMetadata(Action::class));
    },
    CharacterRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new CharacterRepository($em, $em->getClassMetadata(Character::class));
    },
    DivisionRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new DivisionRepository($em, $em->getClassMetadata(Division::class));
    },
    EsiTypeRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new EsiTypeRepository($em, $em->getClassMetadata(EsiType::class));
    },
    ExternalGroupRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new ExternalGroupRepository($em, $em->getClassMetadata(ExternalGroup::class));
    },
    PermissionRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new PermissionRepository($em, $em->getClassMetadata(Permission::class));
    },
    RequestRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new RequestRepository($em, $em->getClassMetadata(Request::class));
    },
    UserRepository::class => function (ContainerInterface $container) {
        $em = $container->get(EntityManagerInterface::class);
        return new UserRepository($em, $em->getClassMetadata(User::class));
    },
];
