<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="external_groups")
 */
class ExternalGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $name = '';

    /**
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="externalGroup", cascade={"remove"})
     */
    private Collection $permissions;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="externalGroups")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
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
        return $this->name;
    }

    /**
     * @return Permission[]
     */
    public function getPermissions(): array
    {
        return $this->permissions->toArray();
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users->toArray();
    }
}
