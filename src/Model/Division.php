<?php

declare(strict_types=1);

namespace EveSrp\Model;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="divisions")
 */
class Division
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="division", cascade={"remove"})
     * @var Collection
     */
    private $permissions;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        
        return $this;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function addPermission(Permission $permission): self
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * @param string $name Filter by type (one of the Permission::* constants)
     * @return Permission[]
     */
    public function getPermissions($name = null): array
    {
        if ($name === null) {
            return $this->permissions->toArray();
        }
        
        $result = [];
        foreach ($this->getPermissions() as $permissions) {
            if ($name === $permissions->getRole()) {
                $result[] = $permissions;
            }
        }
        return $result;
    }

    /**
     * @param string $role Filter by type (one of the Permission::* constants)
     * @param int $groupId An ExternalGroup::id
     * @return bool
     */
    public function hasPermission($role, $groupId): bool
    {
        foreach ($this->getPermissions() as $permissions) {
            if ($role === $permissions->getRole() && $groupId === $permissions->getExternalGroup()->getId()) {
                return true;
            }
        }
        return false;
    }
}
