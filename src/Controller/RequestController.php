<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Misc\KillMailService;
use EveSrp\Repository\RequestRepository;
use EveSrp\Misc\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
    use TwigResponse;

    public function __construct(
        private UserService $userService,
        private KillMailService $killMailService,
        private RequestRepository $requestRepository,
        private EntityManagerInterface  $entityManager,
        Environment $environment
    ) {
        $this->twigResponse($environment);
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
        # TODO page review or pay
        return $this->showPage($response, $args['id']);
    }

    private function showPage($response, $id): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find($id);
        $error = null;
        $shipTypeId = null;
        $killItems = null;
        $killError = null;

        if (!$srpRequest) {
            $error = 'Request not found.';
        } elseif (!$this->userService->maySeeRequest($srpRequest)) {
            $srpRequest = null;
            $error = 'Not authorized to view this request.';
        }

        if ($srpRequest) {
            $this->killMailService->addMissingURLs($srpRequest);
            $killMail = $srpRequest->getKillMail();
            if (empty($killMail)) {
                $killMailOrError = $this->killMailService->getKillMail($srpRequest->getEsiLink());
                if ($killMailOrError instanceof \stdClass) {
                    $killMail = $killMailOrError;
                    $srpRequest->setKillMail($killMail);
                    $this->entityManager->flush();
                } else {
                    $killError = $killMailOrError;
                }
            }
            if ($killMail instanceof \stdClass) {
                $shipTypeId = $killMail->victim->ship_type_id;
                $killItems = $this->killMailService->sortItems($killMail->victim->items, $killMail->killmail_id);
            }
        }

        return $this->render($response, 'pages/request.twig', [
            'request' => $srpRequest,
            'error' => $error,
            'shipTypeId' => $shipTypeId,
            'items' => $killItems,
            'killError' => $killError,
        ]);
    }
}
