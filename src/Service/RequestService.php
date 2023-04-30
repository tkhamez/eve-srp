<?php

declare(strict_types=1);

namespace EveSrp\Service;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Model\Action;
use EveSrp\Model\Division;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Security;
use EveSrp\Type;

class RequestService
{
    public function __construct(
        private UserService $userService,
        private DivisionRepository $divisionRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function mayChangeDivision(Request $request): bool
    {
        if ($this->userService->hasRole(Security::GLOBAL_ADMIN)) {
            return true;
        }
        return $this->userService->hasDivisionRole($request->getDivision(), Permission::REVIEW);
    }

    /**
     * @return Division[]
     */
    public function getDivisionsWithEditPermission(): array
    {
        if ($this->userService->hasRole(Security::GLOBAL_ADMIN)) {
            return $this->divisionRepository->findBy([], ['name' => 'ASC']);
        }
        return $this->userService->getDivisionsWithRoles([Permission::REVIEW, Permission::PAY]);
    }

    public function mayChangeStatusManually(Request $request): bool
    {
        return $this->userService->hasAnyDivisionRole($request->getDivision(), [Permission::REVIEW, Permission::PAY]);
    }

    /**
     * Returns allowed new statuses based on current status and user permissions.
     *
     * @return string[]
     */
    public function getAllowedNewStatuses(Request $request): array
    {
        $division = $request->getDivision();
        if (!$division) {
            return [];
        }

        $permissions = $this->userService->getRolesForDivision($division);

        // New status based on current status
        $newStatuses = [];
        if ($request->getStatus() === Type::INCOMPLETE) {
            $newStatuses = [Type::INCOMPLETE, Type::IN_PROGRESS];
        }
        if (in_array($request->getStatus(), [Type::OPEN, Type::IN_PROGRESS])) {
            $newStatuses = [Type::INCOMPLETE, Type::OPEN, Type::IN_PROGRESS, Type::APPROVED, Type::REJECTED];
        }
        if ($request->getStatus() === Type::APPROVED) {
            $newStatuses = [Type::OPEN, Type::IN_PROGRESS, Type::APPROVED, Type::PAID];
        }
        if ($request->getStatus() === Type::REJECTED) {
            $newStatuses = [Type::OPEN, Type::IN_PROGRESS];
        }
        if ($request->getStatus() === Type::PAID) {
            $newStatuses = [Type::OPEN, Type::IN_PROGRESS];
        }

        // New status based on permissions
        $permissionStatues = [];
        if (in_array(Permission::REVIEW, $permissions)) {
            array_push(
                $permissionStatues,
                Type::INCOMPLETE,
                Type::OPEN,
                Type::IN_PROGRESS,
                Type::APPROVED,
                Type::REJECTED
            );
        }
        if (in_array(Permission::PAY, $permissions)) {
            array_push($permissionStatues, Type::OPEN, Type::IN_PROGRESS, Type::APPROVED, Type::PAID);
        }

        $statuses = array_intersect($newStatuses, $permissionStatues);

        return array_values(array_unique($statuses));
    }

    public function mayChangePayout(Request $request): bool
    {
        return
            $this->userService->hasDivisionRole($request->getDivision(), Permission::REVIEW) &&
            in_array($request->getStatus(), [Type::OPEN, Type::IN_PROGRESS]) ;
    }

    public function mayAddComment(Request $request): bool
    {
        $user = $this->userService->getAuthenticatedUser();

        // Submitter permission
        if ($request->getUser()?->getId() === $user->getId() && $request->getStatus() !== Type::IN_PROGRESS) {
            return true;
        }

        // Editor permission
        if (
            (
                in_array($request->getStatus(), [Type::OPEN, Type::IN_PROGRESS]) &&
                $this->userService->hasAnyDivisionRole($request->getDivision(), [Permission::REVIEW])
            ) || (
                $request->getStatus() === Type::APPROVED &&
                $this->userService->hasAnyDivisionRole($request->getDivision(), [Permission::PAY])
            )
        ) {
            return true;
        }

        return false;
    }

    public function maySave(Request $request): bool
    {
        return
            $this->mayChangeDivision($request) ||
            $this->mayChangeStatusManually($request) ||
            $this->mayChangePayout($request) ||
            $this->mayAddComment($request);
    }

    public function validateInputAndPermission(
        Request $request,
        ?int $newDivision = null,
        ?string $newStatus = null,
        int|string|null $newBasePayout = null,
        string $newComment = '',
    ): bool {
        if ($newDivision !== null) {
            if (!$this->mayChangeDivision($request)) {
                return false;
            }

            $allowedDivisionIds = array_map(function (Division $division) {
                return $division->getId();
            }, $this->getDivisionsWithEditPermission());

            if (!in_array($newDivision, $allowedDivisionIds)) {
                return false;
            }
        }

        if ($newStatus !== null) {
            if (
                !$this->mayChangeStatusManually($request) ||
                !in_array($newStatus, $this->getAllowedNewStatuses($request))
            ) {
                return false;
            }
        }

        if ($newBasePayout !== null) {
            if (!$this->mayChangePayout($request)) {
                return false;
            }
        }

        if ($newComment !== '') {
            if (!$this->mayAddComment($request)) {
                return false;
            }
        }

        return true;
    }

    public function save(
        Request $request,
        ?int $newDivision = null,
        ?string $newStatus = null,
        int|string|null $newBasePayout = null, // empty string to remove, null to ignore
        string $newComment = '',
    ): void {
        if ($request->getStatus() !== Type::INCOMPLETE) { // check old status
            $request->setLastEditor($this->userService->getAuthenticatedUser());
        }

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
            $request->setBasePayout($newBasePayout !== '' ? (int)$newBasePayout : null);

            $action = new Action();
            $action->setCreated(new \DateTime());
            $action->setUser($this->userService->getAuthenticatedUser());
            $action->setCategory(Type::COMMENT);

            if ($newBasePayout === '') {
                $action->setNote(
                    'Removed based payout' .
                    // Note: $oldPayout *should* never be null here, that's handled in the RequestController.
                    ($oldPayout !== null ? ', old value was ' . number_format($oldPayout) . ' ISK.' : '.')
                );
            } elseif ($oldPayout !== null) {
                $action->setNote(
                    'Changed base payout from ' . number_format($oldPayout) . ' to ' .
                    number_format($newBasePayout) . ' ISK.'
                );
            } else {
                $action->setNote('Set base payout to ' . number_format($newBasePayout) . ' ISK.');
            }

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
}
