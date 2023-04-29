<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="users",
 *     options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_520_ci"},
 *     indexes={
 *         @ORM\Index(name="users_name_idx", columns={"name"})
 *     }
 * )
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true, name="external_account_id", length=255)
     */
    private ?string $externalAccountId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToMany(targetEntity="ExternalGroup", inversedBy="users")
     * @ORM\JoinTable(
     *     name="user_external_group",
     *     inverseJoinColumns={@ORM\JoinColumn(name="external_group_id")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private Collection $externalGroups;

    /**
     * @ORM\OneToMany(targetEntity="Character", mappedBy="user")
     */
    private Collection $characters;

    /**
     * @ORM\OneToMany(targetEntity="Request", mappedBy="user")
     * @ORM\OrderBy({"created" = "DESC"})
     */
    private Collection $requests;

    public function __construct()
    {
        $this->externalGroups = new ArrayCollection();
        $this->characters = new ArrayCollection();
        $this->requests = new ArrayCollection();
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
        return (string)$this->name;
    }

    public function getExternalAccountId(): ?string
    {
        return $this->externalAccountId;
    }

    public function setExternalAccountId(?string $externalAccountId): void
    {
        $this->externalAccountId = $externalAccountId;
    }

    public function addExternalGroup(ExternalGroup $externalGroups): self
    {
        $this->externalGroups[] = $externalGroups;

        return $this;
    }

    public function removeExternalGroup(ExternalGroup $externalGroups): bool
    {
        return $this->externalGroups->removeElement($externalGroups);
    }

    /**
     * @return ExternalGroup[]
     */
    public function getExternalGroups(): array
    {
        return array_values($this->externalGroups->toArray());
    }

    public function hasExternalGroup(string $name): bool
    {
        foreach ($this->getExternalGroups() as $group) {
            if ($group->getName() === $name) {
                return true;
            }
        }
        return false;
    }
    
    public function addCharacter(Character $character): self
    {
        foreach ($this->getCharacters() as $existingCharacter) {
            if ($existingCharacter->getId() === $character->getId()) {
                return $this;
            }
        }

        $this->characters[] = $character;

        return $this;
    }

    public function removeCharacter(Character $character): bool
    {
        return $this->characters->removeElement($character);
    }
    
    /**
     * @return Character[]
     */
    public function getCharacters(): array
    {
        return array_values($this->characters->toArray());
    }

    /**
     * @return Request[]
     */
    public function getRequests(): array
    {
        return array_values($this->requests->toArray());
    }
}
