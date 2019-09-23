<?php

declare(strict_types=1);

namespace Brave\EveSrp;

use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use SlimSession\Helper;

class SessionHandler extends Helper implements SessionHandlerInterface
{
    /**
     * SessionHandler constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
    }
}
