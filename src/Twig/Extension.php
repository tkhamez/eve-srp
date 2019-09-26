<?php

declare(strict_types=1);

namespace Brave\EveSrp\Twig;

use Brave\EveSrp\Provider\RoleProvider;
use Psr\Container\ContainerInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /**
     * @var RoleProviderInterface
     */
    private $roleProvider;

    public function __construct(ContainerInterface $container)
    {
        $this->roleProvider = $container->get(RoleProvider::class);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('hasRole', [$this, 'hasRole']),
        ];
    }

    /** @noinspection PhpUnused */
    public function hasRole($role)
    {
        return in_array($role, $this->roleProvider->getUserRoles());
    }
}
