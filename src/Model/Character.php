<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="characters")
 */
class Character
{
    /**
     * EVE character ID.
     *
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="NONE")
     * @var integer|null
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name = '';

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private bool $main = false;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="characters")
     */
    private ?User $user = null;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
    
    public function getId(): int
    {
        return (int) $this->id;
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

    public function setMain(bool $main): self
    {
        $this->main = $main;

        return $this;
    }

    public function getMain(): bool
    {
        return $this->main;
    }

    public function setUser(User $user = null): self
    {
        $this->user = $user;
        
        return $this;
    }
    
    public function getUser(): ?User
    {
        return $this->user;
    }
}
