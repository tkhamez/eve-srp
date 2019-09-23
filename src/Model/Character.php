<?php

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Doctrine\Common\Collections\Collection;
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
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="characters")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="Request", mappedBy="character")
     * @var Collection
     */
    private $requests;

    public function setId(int $od): self
    {
        $this->id = $od;

        return $this;
    }
    
    public function getId(): int
    {
        return (int) $this->id;
    }
    
    public function setUser(User $user): self
    {
        $this->user = $user;
        
        return $this;
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
}
