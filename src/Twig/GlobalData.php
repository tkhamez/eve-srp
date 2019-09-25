<?php

declare(strict_types=1);

namespace Brave\EveSrp\Twig;

use Brave\Sso\Basics\EveAuthentication;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Container\ContainerInterface;

class GlobalData
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /** @noinspection PhpUnused */
    public function appTitle(): string
    {
        return $this->container->get('settings')['brave.serviceName'];
    }

    /** @noinspection PhpUnused */
    public function footerText(): string
    {
        return $this->container->get('settings')['FOOTER_TEXT'];
    }

    /** @noinspection PhpUnused */
    public function userName(): string
    {
        return $this->getUser() ? $this->getUser()->getCharacterName() : '';
    }

    private function getUser(): ?EveAuthentication
    {
        $session =  $this->container->get(SessionHandlerInterface::class);
        return  $session ? $session->get('eveAuth') : null;
    }
}
