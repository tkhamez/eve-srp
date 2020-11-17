<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Exception;
use EveSrp\FlashMessage;
use EveSrp\Model\Division;
use EveSrp\Model\ExternalGroup;
use EveSrp\Model\Permission;
use EveSrp\Provider\InterfaceGroupProvider;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\ExternalGroupRepository;
use EveSrp\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AdminController
{
    use RequestParameter;
    use TwigResponse;

    /**
     * @var InterfaceGroupProvider
     */
    private $groupProvider;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    
    /**
     * @var DivisionRepository
     */
    private $divisionRepository;

    /**
     * @var ExternalGroupRepository
     */
    private $groupRepository;

    /**
     * @var FlashMessage
     */
    private $flashMessage;

    /**
     * @var \EveSrp\Service\UserService
     */
    private $userService;

    /**
     * @var array
     */
    private $validRoles = [Permission::SUBMIT, Permission::REVIEW, Permission::PAY, Permission::ADMIN];
    
    public function __construct(ContainerInterface $container)
    {
        $this->groupProvider = $container->get(InterfaceGroupProvider::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->divisionRepository = $container->get(DivisionRepository::class);
        $this->groupRepository = $container->get(ExternalGroupRepository::class);
        $this->flashMessage = $container->get(FlashMessage::class);
        $this->userService = $container->get(UserService::class);

        $this->twigResponse($container->get(Environment::class));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function divisions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = $this->divisionRepository->findBy([], ['name' => 'ASC']);

        return $this->render($response, 'pages/admin-divisions.twig', ['divisions' => $divisions]);
    }

    public function newDivision(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = (string) $this->paramPost($request, 'name');
        if ($name !== '') {
            $division = $this->divisionRepository->findOneBy(['name' => $name]);
            if ($division === null) {
                $division = new Division();
                $division->setName($name);
                $this->entityManager->persist($division);
                $this->entityManager->flush();
                $this->flashMessage->addMessage('Division added.', FlashMessage::TYPE_SUCCESS);
            } else {
                $this->flashMessage->addMessage('A division with that name already exists.', FlashMessage::TYPE_INFO);
            }
        } else {
            $this->flashMessage->addMessage('Please enter a name.', FlashMessage::TYPE_WARNING);
        }

        return $response->withHeader('Location', '/admin/divisions');
    }

    public function deleteDivision(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->paramPost($request, 'id');
        if ($id) {
            $division = $this->divisionRepository->find($id);
            if ($division) {
                $this->entityManager->remove($division);
                $this->entityManager->flush();
                $this->flashMessage->addMessage('Division deleted.', FlashMessage::TYPE_SUCCESS);
            }
        }

        return $response->withHeader('Location', '/admin/divisions');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function groups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $groups = $this->groupRepository->findBy([], ['name' => 'ASC']);

        return $this->render($response, 'pages/admin-groups.twig', ['groups' => $groups]);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function syncGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $externalGroupNames = $this->groupProvider->getAvailableGroups();
        } catch (Exception $e) {
            error_log('AdminController::syncGroups(): ' . $e->getMessage());
            $this->flashMessage->addMessage('Failed to sync groups.', FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/admin/groups');
        }

        if (count($externalGroupNames) > 0) { // don't do anything if result is empty

            // add groups
            foreach ($externalGroupNames as $externalGroupName) {
                $group = $this->groupRepository->findOneBy(['name' => $externalGroupName]);
                if (! $group) {
                    $group = new ExternalGroup();
                    $group->setName($externalGroupName);
                    $this->entityManager->persist($group);
                }
            }

            // remove groups
            foreach ($this->groupRepository->findBy([]) as $externalGroup) {
                if (! in_array($externalGroup->getName(), $externalGroupNames)) {
                    $this->entityManager->remove($externalGroup);
                }
            }

            $this->entityManager->flush();
        }

        $this->flashMessage->addMessage('Update done.', FlashMessage::TYPE_SUCCESS);
        return $response->withHeader('Location', '/admin/groups');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function permissions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = [];
        foreach ($this->divisionRepository->findBy([], ['name' => 'ASC']) as $division) {
            if ($this->userService->hasDivisionRole($division->getId(), Permission::ADMIN)) {
                $divisions[] = $division;
            }
        }

        return $this->render($response, 'pages/admin-permissions.twig', [
            'divisions' => $divisions,
            'roles' => $this->validRoles,
            'groups' => $this->groupRepository->findBy([]),
        ]);
    }

    public function savePermissions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $this->paramPost($request, 'id');
        $groups = $this->paramPost($request, 'groups');

        $success = null;
        $division = null;
        if ($id && $groups && is_array($groups)) {
            $division = $this->divisionRepository->find($id);
            if ($division && $this->userService->hasDivisionRole($division->getId(), Permission::ADMIN)) {
                foreach ($groups as $role => $groupIds) {
                    if (! is_array($groupIds)) {
                        // nothing was selected
                        $groupIds = [];
                    }
                    $success = $success === false ? false : $this->updateDivision($division, (string) $role, $groupIds);
                }
            }
        }
        if ($success && $division) {
            $this->flashMessage->addMessage(
                'Permissions for division "'.$division->getName().'" save.',
                FlashMessage::TYPE_SUCCESS
            );
        } else {
            $this->flashMessage->addMessage('Failed to save permissions.', FlashMessage::TYPE_WARNING);
        }

        return $response->withHeader('Location', '/admin/permissions');
    }

    private function updateDivision(Division $division, string $role, array $groupIds): bool
    {
        if (! in_array($role, $this->validRoles)) {
            return false;
        }

        // collect valid group IDs and add permissions
        $validGroupIds = [];
        foreach ($groupIds as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if (! $group) {
                continue;
            }
            $validGroupIds[] = $group->getId();
            if (! $division->hasPermission($role, $group->getId())) {
                $newPermission = new Permission();
                $newPermission->setExternalGroup($group);
                $newPermission->setRole($role);
                $newPermission->setDivision($division);
                $division->addPermission($newPermission);
                $this->entityManager->persist($newPermission);
            }
        }

        // remove permissions
        foreach ($division->getPermissions($role) as $existingPermission) {
            if (! in_array($existingPermission->getExternalGroup()->getId(), $validGroupIds)) {
                $this->entityManager->remove($existingPermission);
            }
        }

        $this->entityManager->flush();

        return true;
    }
}
