<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
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
    private $name = '';

    /**
     * @ORM\ManyToMany(targetEntity="ExternalGroup", inversedBy="users")
     * @ORM\JoinTable(name="user_external_group",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="external_group_id ", referencedColumnName="id")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $externalGroups;

    /**
     * @ORM\OneToMany(targetEntity="Character", mappedBy="user")
     * @var Collection
     */
    private $characters;

    /**
     * @ORM\OneToMany(targetEntity="Request", mappedBy="submitter")
     * @ORM\OrderBy({"created" = "ASC"})
     * @var Collection
     */
    private $requests;

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
        return (string) $this->name;
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
        return $this->externalGroups->toArray();
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
        return $this->characters->toArray();
    }

    /**
     * @return Request[]
     */
    public function getRequests(): array
    {
        return $this->requests->toArray();
    }
}
