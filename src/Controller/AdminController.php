<?php

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Model\Division;
use Brave\EveSrp\Model\ExternalGroup;
use Brave\EveSrp\Model\Permission;
use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\Repository\DivisionRepository;
use Brave\EveSrp\Repository\ExternalGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
     * @var array
     */
    private $validRoles = [Permission::SUBMIT, Permission::REVIEW, Permission::PAY, Permission::ADMIN];
    
    public function __construct(ContainerInterface $container) {
        $this->twig = $container->get(Environment::class);
        $this->groupProvider = $container->get(GroupProviderInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->divisionRepository = $container->get(DivisionRepository::class);
        $this->groupRepository = $container->get(ExternalGroupRepository::class);
    }

    /** @noinspection PhpUnused */
    public function divisions(
        /** @noinspection PhpUnusedParameterInspection */
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        $divisions = $this->divisionRepository->findBy([]);

        try {
            $content = $this->twig->render('admin-divisions.twig', ['divisions' => $divisions]);
        } catch (Exception $e) {
            error_log('AdminController' . $e->getMessage());
            $content = '';
        }
        
        /** @noinspection PhpUnhandledExceptionInspection */
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
            }
        }

        /** @noinspection PhpUnhandledExceptionInspection */
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
            }
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        return $response->withHeader('Location', '/admin/divisions');
    }

    /** @noinspection PhpUnused */
    public function groups(
        /** @noinspection PhpUnusedParameterInspection */
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        $groups = $this->groupRepository->findBy([], ['name' => 'ASC']);
            
        try {
            $content = $this->twig->render('admin-groups.twig', ['groups' => $groups]);
        } catch (Exception $e) {
            error_log('AdminController' . $e->getMessage());
            $content = '';
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $response->getBody()->write($content);

        return $response;
    }

    /** @noinspection PhpUnused */
    public function syncGroups(
        /** @noinspection PhpUnusedParameterInspection */
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $externalGroupNames = $this->groupProvider->getAvailableGroups();
        
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
        
        /** @noinspection PhpUnhandledExceptionInspection */
        return $response->withHeader('Location', '/admin/groups');
    }

    /** @noinspection PhpUnused */
    public function permissions(
        /** @noinspection PhpUnusedParameterInspection */
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        # TODO filter by division admin role
        $divisions = $this->divisionRepository->findBy([], ['name' => 'ASC']);
        
        $groups = $this->groupRepository->findBy([]);
        
        try {
            $content = $this->twig->render('admin-permissions.twig', [
                'divisions' => $divisions,
                'roles' => $this->validRoles,
                'groups' => $groups,
            ]);
        } catch (Exception $e) {
            error_log('AdminController' . $e->getMessage());
            $content = '';
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $response->getBody()->write($content);

        return $response;
    }

    /** @noinspection PhpUnused */
    public function savePermissions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        # TODO check division admin role
        
        $id = $this->paramPost($request, 'id');
        $groups = $this->paramPost($request, 'groups');

        if ($id && $groups && is_array($groups)) {
            $division = $this->divisionRepository->find($id);
            if ($division) {
                foreach ($groups as $role => $groupIds) {
                    if (! is_array($groupIds)) {
                        // nothing was selected
                        $groupIds = [];
                    }
                    $this->updateDivision($division, (string) $role, $groupIds);
                }
            }
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        return $response->withHeader('Location', '/admin/permissions');
    }

    private function updateDivision(Division $division, string $role, array $groupIds)
    {
        if (! in_array($role, $this->validRoles)) {
            return;
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
    }
}
