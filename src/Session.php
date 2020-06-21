<?php

declare(strict_types=1);

namespace Brave\EveSrp;

use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;
use SlimSession\Helper;

class Session extends Helper implements SessionHandlerInterface
{
    public function __construct(ContainerInterface $container)
    {
    }
}
