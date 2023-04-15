<?php

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\FlashMessage;
use EveSrp\Misc\RequestService;
use EveSrp\Misc\UserService;
use EveSrp\Model\Division;
use EveSrp\Model\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function __construct(
        private UserService $userService,
        private FlashMessage $flashMessage,
        private RequestService $requestService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasRole', [$this, 'hasRole']),
            new TwigFunction('hasAnyRole', [$this, 'hasAnyRole']),
            new TwigFunction('mayChangeDivision', [$this, 'mayChangeDivision']),
            new TwigFunction('getDivisionsWithEditPermission', [$this, 'getDivisionsWithEditPermission']),
            new TwigFunction('mayChangeStatus', [$this, 'mayChangeStatus']),
            new TwigFunction('getChangeableStatus', [$this, 'getChangeableStatus']),
            new TwigFunction('mayChangePayout', [$this, 'mayChangePayout']),
            new TwigFunction('mayAddComment', [$this, 'mayAddComment']),
            new TwigFunction('maySave', [$this, 'maySave']),
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

    public function mayChangeDivision(Request $request): bool
    {
        return $this->requestService->mayChangeDivision($request);
    }

    public function getDivisionsWithEditPermission(): array
    {
        return $this->requestService->getDivisionsWithEditPermission();
    }

    public function mayChangeStatus(Request $request): bool
    {
        return $this->requestService->mayChangeStatus($request);
    }

    public function getChangeableStatus(Division $division): array
    {
        return $this->requestService->getChangeableStatus($division);
    }

    public function mayChangePayout(Request $request): bool
    {
        return $this->requestService->mayChangePayout($request);
    }

    public function mayAddComment(Request $request): bool
    {
        return $this->requestService->mayAddComment($request);
    }

    public function maySave(Request $request): bool
    {
        return $this->requestService->maySave($request);
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
