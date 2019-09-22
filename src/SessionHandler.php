<?php
namespace Brave\EveSrp;

use Brave\Sso\Basics\SessionHandlerInterface;
use SlimSession\Helper;

/**
 *
 */
class SessionHandler extends Helper implements SessionHandlerInterface
{
    /**
     * SessionHandler constructor.
     *
     * @param \Psr\Container\ContainerInterface $container
     */
    public function __construct(\Psr\Container\ContainerInterface $container)
    {

    }
}
