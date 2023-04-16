<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="modifiers", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_520_ci"})
 */
class Modifier
{
    public const TYPE_RELATIVE = 'relative';

    public const TYPE_ABSOLUTE = 'absolute';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTime $created = null;

    /**
     * relative or absolute
     *
     * @ORM\Column(type="string", name="mod_type", length=8)
     * @see Type
     */
    private ?string $modType = null;

    /**
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="modifiers")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Request $request = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    private ?string $note = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="voided_user_id")
     */
    private ?User $voidedUser = null;

    /**
     * @ORM\Column(type="datetime", name="voided_time", nullable=true)
     */
    private ?\DateTime $voidedTime = null;

    /**
     * @ORM\Column(type="bigint", name="mod_value")
     */
    private ?int $modValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreated(?\DateTime $created): void
    {
        $this->created = $created;
    }

    /** @noinspection PhpUnused */
    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setModType(?string $modType): void
    {
        $this->modType = $modType;
    }

    /** @noinspection PhpUnused */
    public function getModType(): ?string
    {
        return $this->modType;
    }

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /** @noinspection PhpUnused */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /** @noinspection PhpUnused */
    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setVoidedUser(?User $voidedUser): void
    {
        $this->voidedUser = $voidedUser;
    }

    /** @noinspection PhpUnused */
    public function getVoidedUser(): ?User
    {
        return $this->voidedUser;
    }

    public function setVoidedTime(?\DateTime $voidedTime): void
    {
        $this->voidedTime = $voidedTime;
    }

    /** @noinspection PhpUnused */
    public function getVoidedTime(): ?\DateTime
    {
        return $this->voidedTime;
    }

    public function setModValue(?int $modValue): void
    {
        $this->modValue = $modValue;
    }

    /** @noinspection PhpUnused */
    public function getModValue(): ?int
    {
        return $this->modValue;
    }
}
