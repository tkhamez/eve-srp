<?php

declare(strict_types=1);

namespace Brave\EveSrp\Twig;

use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /**
     * @var SessionHandlerInterface
     */
    private $session;

    public function __construct(ContainerInterface $container)
    {
        $this->session = $container->get(SessionHandlerInterface::class);
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
        return in_array($role, $this->session->get('roles'));
    }
}
