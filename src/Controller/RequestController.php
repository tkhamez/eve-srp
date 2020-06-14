<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Controller\Traits\TwigResponse;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
    use TwigResponse;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get(UserService::class);
        $this->requestRepository = $container->get(RequestRepository::class);

        $this->twigResponse($container->get(Environment::class));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # page for submitter
        return $this->showPage($response, $args['id']);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # page review or pay
        return $this->showPage($response, $args['id']);
    }

    private function showPage($response, $id)
    {
        $srpRequest = $this->requestRepository->find($id);
        $error = null;

        if (! $srpRequest) {
            $error = 'Request not found.';
        } elseif (! $this->userService->maySee($srpRequest)) {
            $srpRequest = null;
            $error = 'Not authorized to view this request.';
        }

        return $this->render($response, 'pages/request.twig', [
            'request' => $srpRequest,
            'error' => $error,
        ]);
    }
}
