<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\SrpException;
use Brave\EveSrp\FlashMessage;
use Brave\EveSrp\Model\Division;
use Brave\EveSrp\Model\ExternalGroup;
use Brave\EveSrp\Model\Permission;
use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\Repository\DivisionRepository;
use Brave\EveSrp\Repository\ExternalGroupRepository;
use Brave\EveSrp\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AdminController
{
    use RequestParamsTrait;
    
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var GroupProviderInterface
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
     * @var UserService
     */
    private $userService;

    /**
     * @var array
     */
    private $validRoles = [Permission::SUBMIT, Permission::REVIEW, Permission::PAY, Permission::ADMIN];
    
    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->groupProvider = $container->get(GroupProviderInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->divisionRepository = $container->get(DivisionRepository::class);
        $this->groupRepository = $container->get(ExternalGroupRepository::class);
        $this->flashMessage = $container->get(FlashMessage::class);
        $this->userService = $container->get(UserService::class);
    }

    /**
     * @throws \Exception
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function divisions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = $this->divisionRepository->findBy([], ['name' => 'ASC']);

        $content = $this->twig->render('pages/admin-divisions.twig', ['divisions' => $divisions]);
        $response->getBody()->write($content);

        return $response;
    }

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpUnused */
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
     * @throws \Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function groups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $groups = $this->groupRepository->findBy([], ['name' => 'ASC']);

        $content = $this->twig->render('pages/admin-groups.twig', ['groups' => $groups]);
        $response->getBody()->write($content);

        return $response;
    }

    /** @noinspection PhpUnused */
    /** @noinspection PhpUnusedParameterInspection */
    public function syncGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $externalGroupNames = $this->groupProvider->getAvailableGroups();
        } catch (SrpException $e) {
            $this->flashMessage->addMessage($e->getMessage(), FlashMessage::TYPE_DANGER);
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
     * @throws \Exception
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

        $content = $this->twig->render('pages/admin-permissions.twig', [
            'divisions' => $divisions,
            'roles' => $this->validRoles,
            'groups' => $this->groupRepository->findBy([]),
        ]);
        $response->getBody()->write($content);

        return $response;
    }

    /** @noinspection PhpUnused */
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
