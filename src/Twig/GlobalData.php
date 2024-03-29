<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\Controller\RequestController;
use EveSrp\Misc\CSRFTokenMiddleware;
use EveSrp\Misc\Util;
use EveSrp\Model\Character;
use EveSrp\Model\User;
use EveSrp\Service\UserService;
use EveSrp\Settings;
use EveSrp\Type;
use SlimSession\Helper;

class GlobalData
{
    public function __construct(private Settings $settings, private UserService $userService, private Helper $session)
    {
    }

    public function url(string $service): string
    {
        return $this->settings['URLs'][$service] ?? '';
    }

    public function appTitle(): string
    {
        return $this->settings['APP_TITLE'];
    }

    public function loginHint(): string
    {
        return nl2br(str_replace(
            '\n',
            "\n",
            Util::replaceMarkdownLink(htmlspecialchars($this->settings['LOGIN_HINT']))
        ));
    }

    public function footerText(): string
    {
        $html = Util::replaceMarkdownLink(htmlspecialchars($this->settings['FOOTER_TEXT']));

        if (!empty($html)) {
            $html .= '<br>';
        }

        return $html;
    }

    public function submitDetailsPlaceholder(): string
    {
        return str_replace('\n', "\n", $this->settings['SUBMIT_DETAILS_PLACEHOLDER']);
    }

    public function submitDetailsHelp(): string
    {
        return Util::replaceMarkdownLink(htmlspecialchars($this->settings['SUBMIT_DETAILS_HELP']));
    }

    public function requestModifierHelp(): string
    {
        return match ($this->settings['MODIFIER_CALCULATION']) {
            RequestController::MODIFIER_SEQUENTIALLY => 'Modifiers are applied in the order in which they were added',
            RequestController::MODIFIER_ABSOLUTE_FIRST =>
                'Absolute modifiers are applied first, then the sum of all relative modifiers.',
            RequestController::MODIFIER_RELATIVE_FIRST =>
                'The sum of all relative modifiers is applied first, then the absolute modifiers.',
            default => 'Error: The modifier calculation configuration is invalid.',
        };
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

    public function statuses(): array
    {
        return [Type::INCOMPLETE, Type::OPEN, Type::IN_PROGRESS, Type::APPROVED, Type::PAID, Type::REJECTED];
    }

    public function csrfFormInput(): string
    {
        $name = CSRFTokenMiddleware::CSRF_KEY_NAME;
        $token = $this->session->get($name);
        return "<input type='hidden' name='$name' value='$token'>";
    }

    private function getUser(): ?User
    {
        return $this->userService->getAuthenticatedUser();
    }
}
