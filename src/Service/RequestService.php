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
        if (!$request->getDivision()) {
            return false;
        }
        return $this->userService->hasDivisionRole($request->getDivision(), Permission::REVIEW);
    }

    /**
     * @return Division[]
     */
    public function getDivisionsWithEditPermission(): array
    {
        if ($this->userService->hasRole(Security::GLOBAL_ADMIN)) {
            return $this->divisionRepository->findBy([]);
        }
        return $this->userService->getDivisionsWithRoles([Permission::REVIEW, Permission::PAY]);
    }

    public function mayChangeStatus(Request $request): bool
    {
        if (
            !$request->getDivision() ||
            !$this->userService->hasAnyDivisionRole($request->getDivision(), [Permission::REVIEW, Permission::PAY]) ||
            $request->getStatus() === Type::INCOMPLETE
        ) {
            return false;
        }

        if (in_array($request->getStatus(), $this->getChangeableStatus($request->getDivision()))) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getChangeableStatus(Division $division): array
    {
        $permissions = $this->userService->getRolesForDivision($division);

        $status = [];
        if (in_array(Permission::REVIEW, $permissions)) {
            $status = array_merge($status, [Type::INCOMPLETE, Type::EVALUATING, Type::APPROVED, Type::REJECTED]);
        }
        if (in_array(Permission::PAY, $permissions)) {
            $status = array_merge($status, [Type::EVALUATING, Type::APPROVED, Type::PAID]);
        }

        return array_values(array_unique($status));
    }

    public function mayChangePayout(Request $request): bool
    {
        return
            $request->getDivision() &&
            $this->userService->hasDivisionRole($request->getDivision(), Permission::REVIEW);
    }

    public function mayAddComment(Request $request): bool
    {
        $user = $this->userService->getAuthenticatedUser();

        // Submitter permission
        if ($request->getStatus() == Type::INCOMPLETE && $request->getUser()?->getId() === $user->getId()) {
            return true;
        }

        if (!$request->getDivision()) {
            return false;
        }

        // Editor permission
        if (
            (
                $request->getStatus() === Type::EVALUATING &&
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
}
