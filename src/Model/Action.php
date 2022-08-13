<?php

declare(strict_types=1);

namespace EveSrp\Model;

use EveSrp\Type;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actions")
 */
class Action
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTime $created = null;

    /**
     * Action category/type: one of the EveSrp\Type constants.
     * 
     * @ORM\Column(type="string", length=16)
     * @see Type
     */
    private ?string $category = null;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="actions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Request $request = null;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @noinspection PhpUnused */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /** @noinspection PhpUnused */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /** @noinspection PhpUnused */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /** @noinspection PhpUnused */
    public function getNote(): ?string
    {
        return $this->note;
    }
}
