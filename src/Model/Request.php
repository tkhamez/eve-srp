<?php

declare(strict_types=1);

namespace EveSrp\Model;

use EveSrp\Type;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "requests",
    options: ["charset" => "utf8mb4", "collate" => "utf8mb4_unicode_520_ci"]
)]
#[ORM\Index(columns: ["status"], name: "requests_status_idx")]
#[ORM\Index(columns: ["corporation_name"], name: "requests_corporation_name_idx")]
#[ORM\Index(columns: ["ship"], name: "requests_ship_idx")]
class Request
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "NONE")]
    #[ORM\Column(type: "bigint")]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private ?DateTime $created = null;

    #[ORM\ManyToOne(targetEntity: "Division")]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Division $division = null;

    #[ORM\ManyToOne(targetEntity: "User", inversedBy: "requests")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: "User")]
    #[ORM\JoinColumn(name: "last_editor")]
    private ?User $lastEditor = null;

    #[ORM\OneToMany(targetEntity: "Action", mappedBy: "request")]
    #[ORM\OrderBy(["created" => "DESC"])]
    private Collection $actions;

    #[ORM\OneToMany(targetEntity: "Modifier", mappedBy: "request")]
    #[ORM\OrderBy(["created" => "DESC"])]
    private Collection $modifiers;

    #[ORM\ManyToOne(targetEntity: "Character")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Character $character = null;

    #[ORM\Column(name: "corporation_id", type: "bigint", nullable: true)]
    private ?int $corporationId = null;

    #[ORM\Column(name: "corporation_name", type: "string", length: 255, nullable: true)]
    private ?string $corporationName = null;

    #[ORM\Column(name: "alliance_id", type: "bigint", nullable: true)]
    private ?int $allianceId = null;

    #[ORM\Column(name: "alliance_name", type: "string", length: 255, nullable: true)]
    private ?string $allianceName = null;

    #[ORM\Column(type: "string", length: 128)]
    private string $ship = '';

    #[ORM\Column(name: "kill_time", type: "datetime")]
    private ?DateTime $killTime = null;

    #[ORM\Column(name: "solar_system", type: "string", length: 32)]
    private string $solarSystem = '';

    /**
     * The "External Kill Link" from the in-game menu.
     *
     */
    #[ORM\Column(name: "esi_hash", type: "string", length: 512, nullable: true)]
    private ?string $esiHash = null;

    #[ORM\Column(type: "text", length: 16777215, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: "text", length: 16777215, nullable: true)]
    private ?string $killMail = null;

    #[ORM\Column(name: "base_payout", type: "bigint", nullable: true)]
    private ?int $basePayout = null;

    #[ORM\Column(type: "bigint", nullable: true)]
    private ?int $payout = null;

    /**
     * Request status: one of the EveSrp\Type constants.
     *
     * @see Type
     */
    #[ORM\Column(type: "string", length: 16)]
    private string $status = '';
    
    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->modifiers = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setLastEditor(User $lastEditor): self
    {
        $this->lastEditor = $lastEditor;

        return $this;
    }

    /**
     * @noinspection PhpUnused
     */
    public function getLastEditor(): ?User
    {
        return $this->lastEditor;
    }

    /**
     * @return Action[]
     * @noinspection PhpUnused
     */
    public function getActions(): array
    {
        return array_values($this->actions->toArray());
    }

    /**
     * @return Modifier[]
     * @noinspection PhpUnused
     */
    public function getModifiers(): array
    {
        return array_values($this->modifiers->toArray());
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

    public function getEsiHash(): ?string
    {
        return $this->esiHash;
    }

    public function setEsiHash(?string $url): self
    {
        $this->esiHash = $url;

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

    public function getKillMail(): ?\stdClass
    {
        return $this->killMail ? json_decode($this->killMail) : null;
    }

    public function setKillMail(?\stdClass $killMail): self
    {
        $this->killMail = $killMail ? json_encode($killMail) : null;

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
