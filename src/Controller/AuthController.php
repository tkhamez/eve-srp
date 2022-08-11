<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Eve\Sso\AuthenticationProvider;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Exception;
use EveSrp\FlashMessage;
use EveSrp\Provider\InterfaceGroupProvider;
use EveSrp\Service\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper;
use Twig\Environment;
use UnexpectedValueException;

class AuthController
{
    use TwigResponse;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var Helper
     */
    private $session;

    /**
     * @var InterfaceGroupProvider
     */
    private $groupProvider;

    /**
     * @var UserService 
     */
    private $userService;

    /**
     * @var AuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * @var FlashMessage
     */
    private $flashMessage;

    public function __construct(ContainerInterface $container)
    {
        $this->settings = $container->get('settings');
        $this->session = $container->get(Helper::class);
        $this->groupProvider = $container->get(InterfaceGroupProvider::class);
        $this->userService = $container->get(UserService::class);
        $this->authenticationProvider = $container->get(AuthenticationProvider::class);
        $this->flashMessage = $container->get(FlashMessage::class);

        $this->twigResponse($container->get(Environment::class));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $state = $this->authenticationProvider->generateState();
        } catch (\Exception $e) {
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
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = (string)($request->getQueryParams()['code'] ?? '');
        $state = (string)($request->getQueryParams()['state'] ?? '');
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
            $this->userService->syncCharacters($user, $eveAuth->getCharacterId());
        } catch (Exception $e) {
            error_log('AuthController::auth(): ' . $e->getMessage());
            $this->flashMessage->addMessage('Failed to sync characters.', FlashMessage::TYPE_DANGER);
        }
        try {
            $this->userService->syncGroups($eveAuth->getCharacterId(), $user);
        } catch (Exception $e) {
            error_log('AuthController::auth(): ' . $e->getMessage());
            $this->flashMessage->addMessage('Failed to sync groups.', FlashMessage::TYPE_DANGER);
        }
        $this->session->set('userId', $user->getId());

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
