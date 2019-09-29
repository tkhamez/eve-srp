<?php

declare(strict_types=1);

namespace Brave\EveSrp\Twig;

use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get(UserService::class);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('hasRole', [$this, 'hasRole']),
            new TwigFunction('hasAnyRole', [$this, 'hasAnyRole']),
        ];
    }

    /** @noinspection PhpUnused */
    public function hasRole($role)
    {
        return $this->hasAnyRole([$role]);
    }

    /** @noinspection PhpUnused */
    public function hasAnyRole(array $roles)
    {
        foreach ($roles as $role) {
            if ($this->userService->hasRole($role)) {
                return true;
            }
        }
        return false;
    }
}
