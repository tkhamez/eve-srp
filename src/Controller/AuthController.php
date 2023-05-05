<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Eve\Sso\AuthenticationProvider;
use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Exception;
use EveSrp\FlashMessage;
use EveSrp\Misc\CSRFTokenMiddleware;
use EveSrp\Service\UserService;
use EveSrp\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;
use Twig\Environment;
use UnexpectedValueException;

class AuthController
{
    use RequestParameter;
    use TwigResponse;

    public function __construct(
        private Settings               $settings,
        private Helper                 $session,
        private UserService            $userService,
        private AuthenticationProvider $authenticationProvider,
        private FlashMessage           $flashMessage,
        Environment                    $environment
    ) {
        $this->twigResponse($environment);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->userService->getAuthenticatedUser()) {
            return $response->withHeader('Location', '/');
        }

        try {
            $state = $this->authenticationProvider->generateState();
        } catch (\Exception) {
            $state = uniqid('srp', true);
        }
        $this->session->set('ssoState', $state);

        return $this->render($response, 'pages/login.twig', [
            'serviceName' => $this->settings['APP_TITLE'],
            'logo'        => $this->settings['APP_LOGO'],
            'logoAltText' => $this->settings['APP_LOGO_ALT'],
            'loginUrl'    => $this->authenticationProvider->buildLoginUrl($state),
        ]);
    }

    /**
     * EVE SSO callback.
     *
     * @throws \LogicException
     * @throws \Exception
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = (string) $this->paramGet($request, 'code', '');
        $state = (string) $this->paramGet($request, 'state', '');
        if (empty($code) || empty($state)) {
            $this->flashMessage->addMessage('Invalid SSO state.', FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }

        try {
            $eveAuth = $this->authenticationProvider->validateAuthenticationV2(
                $state,
                (string)$this->session->get('ssoState'),
                $code
            );
        } catch (UnexpectedValueException $e) {
            $this->flashMessage->addMessage($e->getMessage(), FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }

        $user = $this->userService->getUser($eveAuth);
        try {
            $user = $this->userService->syncCharacters($user, $eveAuth->getCharacterId());
        } catch (Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
            $this->flashMessage->addMessage('Failed to sync characters.', FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }
        try {
            $this->userService->syncGroups($user);
        } catch (Exception $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
            $this->flashMessage->addMessage('Failed to sync groups.', FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }

        $this->session->set('userId', $user->getId());
        $this->session->set(CSRFTokenMiddleware::CSRF_KEY_NAME, bin2hex(random_bytes(32)));

        return $response->withHeader('Location', '/');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->session->set('userId', null);

        return $response->withHeader('Location', '/');
    }
}
