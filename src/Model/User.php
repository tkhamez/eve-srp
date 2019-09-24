<?php

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
     * @ORM\OneToMany(targetEntity="Character", mappedBy="user")
     * @var Collection
     */
    private $characters;

    /**
     * @ORM\OneToMany(targetEntity="Request", mappedBy="submitter")
     * @var Collection
     */
    private $requests;

    public function __construct()
    {
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
}
