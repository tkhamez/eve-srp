<?php

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\FlashMessage;
use EveSrp\Service\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    private UserService $userService;

    private FlashMessage $flashMessage;

    public function __construct(UserService $userService, FlashMessage $flashMessage)
    {
        $this->userService = $userService;
        $this->flashMessage = $flashMessage;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasRole', [$this, 'hasRole']),
            new TwigFunction('hasAnyRole', [$this, 'hasAnyRole']),
            new TwigFunction('flashMessages', [$this, 'flashMessages']),
        ];
    }

    public function hasRole($role): bool
    {
        return $this->hasAnyRole([$role]);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->userService->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function flashMessages(): string
    {
        $html = [];
        foreach ($this->flashMessage->getMessages() as $message) {
            $html[] = '<div class="alert alert-'.$message[1].'">'.htmlspecialchars($message[0]).'</div>';
        }
        return implode("\n", $html);
    }
}
