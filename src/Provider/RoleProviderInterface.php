<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Psr\Container\ContainerInterface;

interface RoleProviderInterface extends \Tkhamez\Slim\RoleAuth\RoleProviderInterface
{
    public function __construct(ContainerInterface $container);

    /**
     * Remove roles from cache, if any.
     */
    public function clear(): void;
}
