<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "external_groups", options: ["charset" => "utf8mb4", "collate" => "utf8mb4_unicode_520_ci"])]
class ExternalGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $name = '';

    #[ORM\OneToMany(targetEntity: "Permission", mappedBy: "externalGroup", cascade: ["remove"])]
    private Collection $permissions;

    #[ORM\ManyToMany(targetEntity: "User", mappedBy: "externalGroups")]
    #[ORM\OrderBy(["name" => "ASC"])]
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
        return array_values($this->permissions->toArray());
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return array_values($this->users->toArray());
    }
}
