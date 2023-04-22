<?php

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\FlashMessage;
use EveSrp\Model\Request;
use EveSrp\Service\ApiService;
use EveSrp\Service\RequestService;
use EveSrp\Service\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function __construct(
        private UserService $userService,
        private FlashMessage $flashMessage,
        private RequestService $requestService,
        private ApiService $apiService,
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
            new TwigFunction('getAllowedNewStatuses', [$this, 'getAllowedNewStatuses']),
            new TwigFunction('mayChangePayout', [$this, 'mayChangePayout']),
            new TwigFunction('mayAddComment', [$this, 'mayAddComment']),
            new TwigFunction('maySave', [$this, 'maySave']),
            new TwigFunction('flashMessages', [$this, 'flashMessages']),
            new TwigFunction('zKillboardUrl', [$this, 'zKillboardUrl']),
            new TwigFunction('esiUrl', [$this, 'esiUrl']),
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

    public function getAllowedNewStatuses(Request $request): array
    {
        return $this->requestService->getAllowedNewStatuses($request);
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
            $html[] =
                '<div class="alert alert-'.$message[1].' alert-dismissible fade show">' .
                    htmlspecialchars($message[0]) .
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
                '</div>';
        }
        return implode("\n", $html);
    }

    public function zKillboardUrl(Request $request): string
    {
        return $this->apiService->getZKillboardUrl($request->getId());
    }

    public function esiUrl(Request $request): string
    {
        return $this->apiService->getEsiKillUrl($request->getId(), (string)$request->getEsiHash());
    }
}
