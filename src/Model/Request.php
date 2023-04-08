<?php

declare(strict_types=1);

namespace EveSrp\Model;

use EveSrp\Type;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="requests", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_520_ci"})
 */
class Request
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
     * @ORM\ManyToOne(targetEntity="Division")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private ?Division $division = null;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="requests")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\OneToMany(targetEntity="Action", mappedBy="request")
     */
    private Collection $actions;

    /**
     * @ORM\ManyToOne(targetEntity="Character")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Character $character = null;

    /**
     * @ORM\Column(type="bigint", nullable=true, name="corporation_id")
     */
    private ?int $corporationId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, name="corporation_name")
     */
    private ?string $corporationName = null;

    /**
     * @ORM\Column(type="bigint", nullable=true, name="alliance_id")
     */
    private ?int $allianceId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, name="alliance_name")
     */
    private ?string $allianceName = null;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private string $ship = '';

    /**
     * @ORM\Column(type="datetime", name="kill_time")
     */
    private ?DateTime $killTime = null;

    /**
     * @ORM\Column(type="string", name="solar_system", length=32)
     */
    private string $solarSystem = '';

    /**
     * @ORM\Column(type="string", name="killboard_url", length=512, nullable=true)
     */
    private ?string $killboardUrl = null;

    /**
     * The "External Kill Link" from the in-game menu.
     * 
     * @ORM\Column(type="string", name="esi_link", length=512, nullable=true)
     */
    private ?string $esiLink = null;
    
    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    private ?string $details = null;

    /**
     * @ORM\Column(type="bigint", name="base_payout", nullable=true)
     */
    private ?int $basePayout = null;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private ?int $payout = null;

    /**
     * Request status: one of the EveSrp\Type constants.
     *
     * @ORM\Column(type="string", length=16)
     * @see Type
     */
    private string $status = '';
    
    public function __construct()
    {
        $this->actions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @noinspection PhpUnused */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $date): self
    {
        $this->created = clone $date;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Action[]
     * @noinspection PhpUnused
     */
    public function getActions(): array
    {
        return $this->actions->toArray();
    }

    /** @noinspection PhpUnused */
    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(Character $character): self
    {
        $this->character = $character;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getCorporationId(): ?int
    {
        return $this->corporationId;
    }

    public function setCorporationId(?int $corporationId): self
    {
        $this->corporationId = $corporationId;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getCorporationName(): ?string
    {
        return $this->corporationName;
    }

    public function setCorporationName(?string $corporationName): self
    {
        $this->corporationName = $corporationName;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getAllianceId(): ?int
    {
        return $this->allianceId;
    }

    public function setAllianceId(?int $allianceId): self
    {
        $this->allianceId = $allianceId;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getAllianceName(): ?string
    {
        return $this->allianceName;
    }

    public function setAllianceName(?string $allianceName): self
    {
        $this->allianceName = $allianceName;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getShip(): string
    {
        return $this->ship;
    }

    public function setShip(string $ship): self
    {
        $this->ship = $ship;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getKillTime(): ?DateTime
    {
        return $this->killTime;
    }

    public function setKillTime(DateTime $dateTime): self
    {
        $this->killTime = $dateTime;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getSolarSystem(): string
    {
        return $this->solarSystem;
    }

    public function setSolarSystem(string $solarSystem): self
    {
        $this->solarSystem = $solarSystem;

        return $this;
    }

    public function getKillboardUrl(): ?string
    {
        return $this->killboardUrl;
    }

    public function setKillboardUrl(?string $url): self
    {
        $this->killboardUrl = $url;

        return $this;
    }

    public function getEsiLink(): ?string
    {
        return $this->esiLink;
    }

    public function setEsiLink(?string $url): self
    {
        $this->esiLink = $url;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getBasePayout(): ?int
    {
        return $this->basePayout;
    }

    public function setBasePayout(?int $basePayout): self
    {
        $this->basePayout = $basePayout;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getPayout(): ?int
    {
        return $this->payout;
    }

    public function setPayout(?int $payout): self
    {
        $this->payout = $payout;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
