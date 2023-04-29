<?php

declare(strict_types=1);

namespace EveSrp\Service;

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

    public function mayChangeStatus(Request $request): bool
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
        if ($request->getStatus() === Type::OPEN || $request->getStatus() === Type::IN_PROGRESS) {
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

        // Status based on permission
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
        if ($request->getStatus() == Type::INCOMPLETE && $request->getUser()?->getId() === $user->getId()) {
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
            $this->mayChangeStatus($request) ||
            $this->mayChangePayout($request) ||
            $this->mayAddComment($request);
    }

    /**
     * @param Request[] $requests
     */
    public function calculatePayoutSum(array $requests): ?int
    {
        if (empty($requests)) {
            return null;
        }

        $sum = 0;
        foreach ($requests as $request) {
            $sum += $request->getPayout();
        }
        return $sum;
    }
}
