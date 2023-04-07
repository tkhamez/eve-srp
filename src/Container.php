<?php

declare(strict_types=1);

namespace EveSrp;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\Api\ApplicationCharactersApi;
use Brave\NeucoreApi\Api\ApplicationGroupsApi;
use Brave\NeucoreApi\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Eve\Sso\AuthenticationProvider;
use EveSrp\Model\Action;
use EveSrp\Model\Character;
use EveSrp\Model\Division;
use EveSrp\Model\EsiType;
use EveSrp\Model\ExternalGroup;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Model\User;
use EveSrp\Provider\ProviderInterface;
use EveSrp\Provider\RoleProvider;
use EveSrp\Repository\ActionRepository;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\EsiTypeRepository;
use EveSrp\Repository\ExternalGroupRepository;
use EveSrp\Repository\PermissionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Twig\Extension;
use EveSrp\Twig\GlobalData;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

final class Container
{
    public static function getDefinition(): array
    {
        return [
            // Settings
            Settings::class => function () {
                return new Settings(
                    require_once ROOT_DIR . '/config/config.php'
                );
            },

            // Slim
            ResponseFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(ResponseFactory::class);
            },

            // Provider
            RoleProviderInterface::class => function (ContainerInterface $container) {
                return $container->get(RoleProvider::class);
            },
            ProviderInterface::class => function (ContainerInterface $container) {
                $class = $container->get(Settings::class)['PROVIDER'];
                return $container->get($class);
            },

            // Guzzle HTTP client
            ClientInterface::class => function (ContainerInterface $container) {
                return new Client([
                    'headers' => ['User-Agent' => $container->get(Settings::class)['HTTP_USER_AGENT']]
                ]);
            },

            // SSO
            AuthenticationProvider::class => function (ContainerInterface $container) {
                $settings = $container->get(Settings::class);
                $provider = new AuthenticationProvider([
                    'clientId' => $settings['SSO_CLIENT_ID'],
                    'clientSecret' => $settings['SSO_CLIENT_SECRET'],
                    'redirectUri' => $settings['SSO_REDIRECT_URI'],
                    'urlAuthorize' => $settings['SSO_URL_AUTHORIZE'],
                    'urlAccessToken' => $settings['SSO_URL_ACCESS_TOKEN'],
                    'urlResourceOwnerDetails' => '',
                    'urlKeySet' => $settings['SSO_URL_JWT_KEY_SET'],
                    'urlRevoke' => 'https://login.eveonline.com/v2/oauth/revoke',
                ]);
                $provider->getProvider()->setHttpClient($container->get(ClientInterface::class));
                return $provider;
            },

            // Neucore API
            Configuration::class => function (ContainerInterface $container) {
                $settings = $container->get(Settings::class);
                $apiKey = base64_encode($settings['NEUCORE_APP_ID'] . ':' . $settings['NEUCORE_APP_TOKEN']);
                $config = Configuration::getDefaultConfiguration();
                $config->setHost($settings['NEUCORE_DOMAIN'].'/api');
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
                $dev = $container->get(Settings::class)['APP_ENV'] === 'dev';
                $options = [];
                if ($dev) {
                    $options['debug'] = true;
                } else {
                    $options['cache'] = ROOT_DIR . '/storage/compilation_cache';
                }
                $loader = new FilesystemLoader(ROOT_DIR . '/templates');
                $loader->addPath(ROOT_DIR . '/web/dist');
                $twig = new Environment($loader, $options);
                if ($dev) {
                    $twig->addExtension($container->get(DebugExtension::class));
                }
                $twig->addGlobal('data', $container->get(GlobalData::class));
                $twig->addExtension($container->get(Extension::class));
                return $twig;
            },

            // Doctrine ORM
            EntityManagerInterface::class => function (ContainerInterface $container) {
                return EntityManager::create(
                    ['url' => $container->get(Settings::class)['DB_URL']],
                    ORMSetup::createAnnotationMetadataConfiguration(
                        [ROOT_DIR . '/src/Model'],
                        $container->get(Settings::class)['APP_ENV'] === 'dev',
                        ROOT_DIR . '/storage'
                    )
                );
            },
            ActionRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, ActionRepository::class, Action::class);
            },
            CharacterRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, CharacterRepository::class, Character::class);
            },
            DivisionRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, DivisionRepository::class, Division::class);
            },
            EsiTypeRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, EsiTypeRepository::class, EsiType::class);
            },
            ExternalGroupRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, ExternalGroupRepository::class, ExternalGroup::class);
            },
            PermissionRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, PermissionRepository::class, Permission::class);
            },
            RequestRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, RequestRepository::class, Request::class);
            },
            UserRepository::class => function (ContainerInterface $container) {
                return self::getRepository($container, UserRepository::class, User::class);
            },
        ];
    }

    /**
     * @throws \Throwable
     */
    private static function getRepository(
        ContainerInterface $container,
        string $repositoryClass,
        string $entityClass
    ): EntityRepository  {
        $em = $container->get(EntityManagerInterface::class); /* @var EntityManagerInterface $em */
        return new $repositoryClass($em, $em->getClassMetadata($entityClass));
    }
}
