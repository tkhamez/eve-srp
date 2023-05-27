<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\FlashMessage;
use EveSrp\Misc\Util;
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
            return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
        }

        // Read new data
        $newDivision = null;
        if ($this->paramPost($request, 'division') !== null) {
            $newDivision = (int)$this->paramPost($request, 'division');
        }
        $newStatus = $this->paramPost($request, 'status');
        // Need to distinguish between null and empty string in save() for base payout.
        $newBasePayout = $this->paramPost($request, 'payout');
        if ($newBasePayout !== '' && $newBasePayout !== null) { // allow '0'
            $newBasePayout = (int)round($this->sanitizeNumberInput($newBasePayout) * Util::ONE_MILLION);
        }
        $newComment = trim((string)$this->paramPost($request, 'comment'));

        // Check if changed
        if ($srpRequest->getDivision()?->getId() === $newDivision) {
            $newDivision = null;
        }
        if ($srpRequest->getStatus() === $newStatus) {
            $newStatus = null;
        }
        if (
            $srpRequest->getBasePayout() === $newBasePayout ||
            ($srpRequest->getBasePayout() === null && $newBasePayout === '')
        ) {
            $newBasePayout = null;
        }
        if ($newDivision === null && $newStatus === null && $newBasePayout === null && $newComment === '') {
            return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
        }

        // Check if there is a base payout if status was changed to approved
        if (
            $newStatus === Type::APPROVED &&
            ($newBasePayout === null || $newBasePayout === '') &&
            $srpRequest->getBasePayout() === null
        ) {
            $this->flashMessage->addMessage(
                'Please add a base payout if you want to approve the request',
                FlashMessage::TYPE_WARNING
            );
            return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
        }

        // Validate and save
        if (!$this->requestService->validateInputAndPermission(
            $srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment
        )) {
            $this->flashMessage->addMessage('Invalid input.', FlashMessage::TYPE_WARNING);
        } else {
            // Change status if submitter added comment.
            $newStatus = $this->adjustStatus($srpRequest, $newStatus, $newComment);

            // Save
            $this->requestService->save($srpRequest, $newDivision, $newStatus, $newBasePayout, $newComment);
            $this->setPayout($srpRequest);
            $this->flashMessage->addMessage('Request updated.', FlashMessage::TYPE_SUCCESS);
        }

        return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
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
        $amount = $this->sanitizeNumberInput((string)$this->paramPost($request, 'amount'));
        $type = (string)$this->paramPost($request, 'type');
        $reason = trim((string)$this->paramPost($request, 'reason'));

        // Validate input
        $validTypes = [
            RequestService::MOD_REL_BONUS,
            RequestService::MOD_REL_DEDUCTION,
            RequestService::MOD_ABS_BONUS,
            RequestService::MOD_ABS_DEDUCTION
        ];
        if (
            $amount === 0.0 ||
            !in_array($type, $validTypes) ||
            ($type == RequestService::MOD_REL_DEDUCTION && $amount > 100)
        ) {
            $this->flashMessage->addMessage('Invalid input.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
        }

        // Adjust amount based on type
        $amount = in_array($type, [RequestService::MOD_ABS_BONUS, RequestService::MOD_ABS_DEDUCTION])
            ? $amount * Util::ONE_MILLION
            : $amount;
        $amount = in_array($type, [RequestService::MOD_REL_DEDUCTION, RequestService::MOD_ABS_DEDUCTION])
            ? $amount * -1
            : $amount;

        // Add modifier
        $modifier = new Modifier();
        $modifier->setCreated(new \DateTime());
        $modifier->setUser($this->userService->getAuthenticatedUser());
        $modifier->setModType(
            in_array($type, [RequestService::MOD_REL_BONUS, RequestService::MOD_REL_DEDUCTION])
                ? Modifier::TYPE_RELATIVE
                : Modifier::TYPE_ABSOLUTE
        );
        $modifier->setModValue((int)round($amount));
        $modifier->setNote($reason);
        $modifier->setRequest($srpRequest);

        $this->entityManager->persist($modifier);
        $this->entityManager->flush();

        $this->setPayout($srpRequest);
        $this->flashMessage->addMessage('Modifier added.', FlashMessage::TYPE_SUCCESS);

        return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
    }

    public function modifierRemove(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
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

        return $response->withHeader('Location', "/request/{$args['id']}")->withStatus(302);
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
            $this->killMailService->addMissingEsiHash($srpRequest);
            $killMail = $srpRequest->getKillMail();
            if (empty($killMail)) {
                $killMailOrError = $this->killMailService->getKillMail($srpRequest);
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

    private function adjustStatus(Request $srpRequest, ?string $newStatus, string $newComment): ?string
    {
        if (
            $srpRequest->getUser()?->getId() === $this->userService->getAuthenticatedUser()?->getId() &&
            $newComment !== '' &&
            $srpRequest->getStatus() !== Type::OPEN
        ) {
            return Type::IN_PROGRESS;
        }
        return $newStatus;
    }

    private function modifierGetRequestCheckPermission(
        ResponseInterface $response,
        int $requestId,
    ): ResponseInterface|Request {
        // Get SRP request
        $srpRequest = $this->requestRepository->find($requestId);
        if (!$srpRequest) {
            return $response->withHeader('Location', "/request/$requestId")->withStatus(302);
        }

        // Check permission
        if (!$this->requestService->mayChangePayout($srpRequest)) {
            $this->flashMessage->addMessage('Permission denied.', FlashMessage::TYPE_WARNING);
            return $response->withHeader('Location', "/request/$requestId")->withStatus(302);
        }

        return $srpRequest;
    }

    private function setPayout(Request $request): void
    {
        $basePayout = $request->getBasePayout();
        if ($basePayout === null) {
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
                    $payout *= 1 - ($modifier->getModValue() * -1 / 100);
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

    private function sanitizeNumberInput(string $input): float
    {
        return (float)preg_replace('/[^0-9.]+/', '', $input);
    }
}
