<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\FlashMessage;
use EveSrp\Model\Action;
use EveSrp\Model\Division;
use EveSrp\Model\Modifier;
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
        if (!$this->validateInputAndPermission($srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment)) {
            $this->flashMessage->addMessage('Invalid input.', FlashMessage::TYPE_WARNING);
        } else {
            // Change status if submitter added comment.
            $newStatus = $this->adjustStatus($srpRequest, $newStatus, $newComment);

            // Save
            $this->save($srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment);
            $this->setPayout($srpRequest);
            $this->flashMessage->addMessage('Request updated.', FlashMessage::TYPE_SUCCESS);
        }

        return $response->withHeader('Location', "/request/{$args['id']}");
    }

    public function modifierAdd(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $check = $this->modifierGetRequestCheckPermission($response, (int)$args['id']);
        if ($check instanceof ResponseInterface) {
            return $check;
        } else {
            $srpRequest = $check;
        }

        // Get input
        $amount = abs((int)str_replace(',', '', (string)$this->paramPost($request, 'amount')));
        $type = (string)$this->paramPost($request, 'type');
        $reason = trim((string)$this->paramPost($request, 'reason'));

        // Validate input
        $validTypes = ['relative-bonus', 'relative-deduction', 'absolute-bonus', 'absolute-deduction'];
        if (
            $amount === 0 ||
            !in_array($type, $validTypes) ||
            ($type == 'relative-deduction' && $amount > 100)
        ) {
            $this->flashMessage->addMessage('Invalid input.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', "/request/{$args['id']}");
        }

        // Add modifier
        $modifier = new Modifier();
        $modifier->setCreated(new \DateTime());
        $modifier->setUser($this->userService->getAuthenticatedUser());
        $modifier->setModType(
            in_array($type, ['relative-bonus', 'relative-deduction']) ?
                Modifier::TYPE_RELATIVE :
                Modifier::TYPE_ABSOLUTE
        );
        $modifier->setModValue(in_array($type, ['relative-deduction', 'absolute-deduction']) ? $amount * -1 : $amount);
        $modifier->setNote($reason);
        $modifier->setRequest($srpRequest);

        $this->entityManager->persist($modifier);
        $this->entityManager->flush();

        $this->setPayout($srpRequest);
        $this->flashMessage->addMessage('Modifier added.', FlashMessage::TYPE_SUCCESS);

        return $response->withHeader('Location', "/request/{$args['id']}");
    }

    public function modifierRemove(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $check = $this->modifierGetRequestCheckPermission($response, (int)$args['id']);
        if ($check instanceof ResponseInterface) {
            return $check;
        } else {
            $srpRequest = $check;
        }

        // Find existing modifier
        $modifierId = (int)$this->paramPost($request, 'id');
        $modifierToRemove = null;
        foreach ($srpRequest->getModifiers() as $modifier) {
            if ($modifier->getId() === $modifierId) {
                $modifierToRemove = $modifier;
                break;
            }
        }

        if ($modifierToRemove) {
            // Remove modifier
            $modifierToRemove->setVoidedTime(new \DateTime());
            $modifierToRemove->setVoidedUser($this->userService->getAuthenticatedUser());
            $this->entityManager->flush();

            $this->setPayout($srpRequest);
            $this->flashMessage->addMessage('Modifier removed.', FlashMessage::TYPE_SUCCESS);
        } else {
            $this->flashMessage->addMessage('Modifier not found.', FlashMessage::TYPE_WARNING);
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

    private function validateInputAndPermission(
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
                !in_array($newStatus, $this->requestService->getAllowedNewStatuses($request))
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
    ): void {
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

        $this->entityManager->flush();
    }

    private function modifierGetRequestCheckPermission(
        ResponseInterface $response,
        int $requestId,
    ): ResponseInterface|Request {
        // Get SRP request
        $srpRequest = $this->requestRepository->find($requestId);
        if (!$srpRequest) {
            return $response->withHeader('Location', "/request/$requestId");
        }

        // Check permission
        if (!$this->requestService->mayChangePayout($srpRequest)) {
            $this->flashMessage->addMessage('Permission denied.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', "/request/$requestId");
        }

        return $srpRequest;
    }

    private function setPayout(Request $request): void
    {
        $basePayout = $request->getBasePayout();
        if (!$basePayout) {
            return;
        }

        $payout = $basePayout;
        $modifiers = $request->getModifiers();

        // Sort modifiers by date created ascending
        usort($modifiers, function (Modifier $a, Modifier $b) {
            return $a->getCreated()->getTimestamp() < $b->getCreated()->getTimestamp() ? -1 : 1;
        });

        foreach ($modifiers as $modifier) {
            if ($modifier->getVoidedTime()) {
                continue;
            }
            if ($modifier->getModType() === Modifier::TYPE_RELATIVE) {
                if ($modifier->getModValue() > 0) {
                    $payout *= 1 + ($modifier->getModValue() / 100);
                } else {
                    $payout *= $modifier->getModValue() * -1 / 100;
                }
            } elseif ($modifier->getModType() === Modifier::TYPE_ABSOLUTE) {
                $payout += $modifier->getModValue();
            }
        }

        if ($payout !== $request->getPayout()) {
            $request->setPayout((int)round($payout));
            $this->entityManager->flush();
        }
    }
}
