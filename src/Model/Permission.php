<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="permissions")
 */
class Permission
{
    /**
     * May submit a request
     */
    public const SUBMIT = 'submit';

    /**
     * May approve a request
     */
    public const REVIEW = 'review';

    /**
     * May payout the ISK.
     */
    public const PAY = 'pay';

    /**
     * Can change division permission.
     */
    public const ADMIN = 'admin';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Division", inversedBy="permissions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Division $division = null;

    /**
     * @ORM\ManyToOne(targetEntity="ExternalGroup", inversedBy="permissions")
     * @ORM\JoinColumn(name="external_group_id", nullable=false)
     */
    private ?ExternalGroup $externalGroup = null;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private ?string $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDivision(Division $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setExternalGroup(ExternalGroup $externalGroup): self
    {
        $this->externalGroup = $externalGroup;

        return $this;
    }

    public function getExternalGroup(): ?ExternalGroup
    {
        return $this->externalGroup;
    }

    /**
     * @param string $role One of the self::* constants.
     * @return $this
     */
    public function setRole(string $role): self
    {
        $this->role = $role;
        
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}
