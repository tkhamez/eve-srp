<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\FlashMessage;
use EveSrp\Model\Action;
use EveSrp\Model\Division;
use EveSrp\Model\Request;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\KillMailService;
use EveSrp\Service\RequestService;
use EveSrp\Service\UserService;
use EveSrp\Type;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
    use RequestParameter;
    use TwigResponse;

    public function __construct(
        private UserService $userService,
        private KillMailService $killMailService,
        private RequestService $requestService,
        private RequestRepository $requestRepository,
        private DivisionRepository $divisionRepository,
        private EntityManagerInterface  $entityManager,
        private FlashMessage $flashMessage,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->showPage($response, $args['id']);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find((int)$args['id']);
        if (!$srpRequest) {
            return $response->withHeader('Location', "/request/{$args['id']}");
        }

        // Read new data
        $newDivision = null;
        if ($this->paramPost($request, 'division') !== null) {
            $newDivision = (int)$this->paramPost($request, 'division');
        }
        $newStatus = $this->paramPost($request, 'status');
        $newBasePayout = null;
        if ($this->paramPost($request, 'payout') !== null) {
            $newBasePayout = abs((int)str_replace(',', '', (string)$this->paramPost($request, 'payout')));
        }
        $newComment = trim((string)$this->paramPost($request, 'comment'));

        // Check if changed
        if ($srpRequest->getDivision()?->getId() === $newDivision) {
            $newDivision = null;
        }
        if ($srpRequest->getStatus() === $newStatus) {
            $newStatus = null;
        }
        if ($srpRequest->getBasePayout() === $newBasePayout) {
            $newBasePayout = null;
        }
        if ($newDivision === null && $newStatus === null && $newBasePayout === null && $newComment === '') {
            return $response->withHeader('Location', "/request/{$args['id']}");
        }

        // Validate and save
        if (!$this->validateInput($srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment)) {
            $this->flashMessage->addMessage('Invalid input.', FlashMessage::TYPE_WARNING);
        } else {
            // Change status if submitter added comment.
            $newStatus = $this->adjustStatus($srpRequest, $newStatus, $newComment);

            if ($this->save($srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment)) {
                $this->flashMessage->addMessage('Request updated.', FlashMessage::TYPE_SUCCESS);
            } else {
                $this->flashMessage->addMessage('Failed to updated request.', FlashMessage::TYPE_DANGER);
            }
        }

        return $response->withHeader('Location', "/request/{$args['id']}");
    }

    private function showPage($response, $id): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find($id);
        $shipTypeId = null;
        $killItems = null;
        $killError = null;

        if (!$srpRequest || !$this->userService->maySeeRequest($srpRequest)) {
            $srpRequest = null;
            $this->flashMessage->addMessage(
                'Request not found or not authorized to view it.',
                FlashMessage::TYPE_WARNING
            );
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
            'shipTypeId' => $shipTypeId,
            'items' => $killItems,
            'killError' => $killError,
        ]);
    }

    private function validateInput(
        Request $request,
        ?int $newDivision,
        ?string $newStatus,
        ?int $newBasePayout,
        string $newComment,
    ): bool {
        if ($newDivision !== null) {
            if (!$this->requestService->mayChangeDivision($request)) {
                return false;
            }

            $allowedDivisionIds = array_map(function (Division $division) {
                return $division->getId();
            }, $this->requestService->getDivisionsWithEditPermission());

            if (!in_array($newDivision, $allowedDivisionIds)) {
                return false;
            }
        }

        if ($newStatus !== null) {
            if (
                !$this->requestService->mayChangeStatus($request) ||
                !in_array($newStatus, $this->requestService->getChangeableStatus($request))
            ) {
                return false;
            }
        }

        if ($newBasePayout !== null) {
            if (!$this->requestService->mayChangePayout($request)) {
                return false;
            }
        }

        if ($newComment !== '') {
            if (!$this->requestService->mayAddComment($request)) {
                return false;
            }
        }

        return true;
    }

    private function adjustStatus(Request $srpRequest, ?string $newStatus, string $newComment): ?string
    {
        if (
            $srpRequest->getUser()?->getId() === $this->userService->getAuthenticatedUser()?->getId() &&
            $srpRequest->getStatus() === Type::INCOMPLETE &&
            $newComment !== ''
        ) {
            return Type::EVALUATING;
        }
        return $newStatus;
    }

    private function save(
        Request $request,
        ?int $newDivision,
        ?string $newStatus,
        ?int $newBasePayout,
        string $newComment,
    ): bool {
        if ($newDivision !== null) {
            $division = $this->divisionRepository->find($newDivision);
            $oldDivision = $request->getDivision();
            $request->setDivision($division);

            $action = new Action();
            $action->setCreated(new \DateTime());
            $action->setUser($this->userService->getAuthenticatedUser());
            $action->setCategory(Type::COMMENT);
            $action->setNote("Moved from division \"{$oldDivision?->getName()}\" to \"{$division->getName()}\".");

            $action->setRequest($request);
            $this->entityManager->persist($action);
        }

        if ($newStatus !== null) {
            $request->setStatus($newStatus);

            $action = new Action();
            $action->setCreated(new \DateTime());
            $action->setUser($this->userService->getAuthenticatedUser());
            $action->setCategory($newStatus);
            if ($newComment !== '') {
                $action->setNote($newComment);
                $newComment = '';
            }

            $action->setRequest($request);
            $this->entityManager->persist($action);
        }

        if ($newBasePayout !== null) {
            $oldPayout = $request->getBasePayout();
            $request->setBasePayout($newBasePayout);

            $action = new Action();
            $action->setCreated(new \DateTime());
            $action->setUser($this->userService->getAuthenticatedUser());
            $action->setCategory(Type::COMMENT);
            $action->setNote(
                'Changed base payout from  ' . number_format($oldPayout) . ' to ' .
                number_format($newBasePayout) . ' ISK.'
            );

            $action->setRequest($request);
            $this->entityManager->persist($action);
        }

        if ($newComment !== '') {
            $action = new Action();
            $action->setCreated(new \DateTime());
            $action->setUser($this->userService->getAuthenticatedUser());
            $action->setCategory(Type::COMMENT);
            $action->setNote($newComment);

            $action->setRequest($request);
            $this->entityManager->persist($action);
        }

        try {
            $this->entityManager->flush();
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
