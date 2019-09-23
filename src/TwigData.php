<?php

declare(strict_types=1);

namespace Brave\EveSrp;

use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;

class TwigData
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getAppTitle(): string
    {
        return $this->container->get('settings')['brave.serviceName'];
    }
    
    public function getUserName(): string
    {
        return $this->getUser() ? $this->getUser()->getCharacterName() : '';
    }

    public function getUser(): ?EveAuthentication
    {
        $session =  $this->container->get(SessionHandlerInterface::class);
        return  $session ? $session->get('eveAuth') : null;
    }
}
