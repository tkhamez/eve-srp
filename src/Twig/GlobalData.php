<?php

declare(strict_types=1);

namespace Brave\EveSrp\Twig;

use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\User;
use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;

class GlobalData
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var UserService 
     */
    private $userService;

    public function __construct(ContainerInterface $container)
    {
        $this->settings = $container->get('settings');
        $this->userService = $container->get(UserService::class);
    }

    /** @noinspection PhpUnused */
    public function appTitle(): string
    {
        return $this->settings['APP_TITLE'];
    }

    /** @noinspection PhpUnused */
    public function footerText(): string
    {
        return $this->settings['FOOTER_TEXT'];
    }

    /** @noinspection PhpUnused */
    public function userName(): string
    {
        return $this->getUser() ? $this->getUser()->getName() : '';
    }

    /** @noinspection PhpUnused */
    public function characters(): array
    {
        return $this->getUser() ? array_map(function(Character $char) {
            return $char->getName();
        }, $this->getUser()->getCharacters()) : [];
    }

    private function getUser(): ?User
    {
        return $this->userService->getAuthenticatedUser();
    }
}
