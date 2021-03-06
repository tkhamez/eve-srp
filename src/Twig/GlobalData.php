<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\Model\Character;
use EveSrp\Model\User;
use EveSrp\Service\UserService;
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

    public function appTitle(): string
    {
        return $this->settings['APP_TITLE'];
    }

    public function faviconUrl(): string
    {
        return $this->settings['APP_FAVICON'];
    }

    public function loginHint(): string
    {
        return $this->replaceMarkdownLink(htmlspecialchars($this->settings['LOGIN_HINT']));
    }

    public function footerText(): string
    {
        return $this->replaceMarkdownLink(htmlspecialchars($this->settings['FOOTER_TEXT']));
    }

    public function userName(): string
    {
        return $this->getUser() ? $this->getUser()->getName() : '';
    }

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

    private function replaceMarkdownLink($text)
    {
        return preg_replace(
            '/\[(.*?)]\((.*?)\)/',
            '<a href="$2" target="_blank">$1</a> ',
            $text
        );
    }
}
