<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Model;

use EveSrp\Type;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="requests")
 */
class Request
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity="Division")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @var Division
     */
    private $division;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="requests")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $submitter;

    /**
     * @ORM\OneToMany(targetEntity="Action", mappedBy="request")
     * @var Collection
     */
    private $actions;

    /**
     * @ORM\ManyToOne(targetEntity="Character")
     * @ORM\JoinColumn(nullable=false)
     * @var Character
     */
    private $pilot;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $corporation;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alliance;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    private $ship = '';

    /**
     * @ORM\Column(type="datetime", name="kill_time")
     * @var DateTime
     */
    private $killTime;

    /**
     * @var string
     * @ORM\Column(type="string", name="solar_system", length=32)
     */
    private $solarSystem = '';

    /**
     * @var string
     * @ORM\Column(type="string", name="killboard_url", length=512, nullable=true)
     */
    private $killboardUrl;

    /**
     * The "External Kill Link" from the in-game menu.
     * 
     * @var string
     * @ORM\Column(type="string", name="esi_link", length=512, nullable=true)
     */
    private $esiLink;
    
    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     * @var string
     */
    private $details;

    /**
     * @ORM\Column(type="integer", name="base_payout", nullable=true)
     * @var integer
     */
    private $basePayout;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    private $payout;

    /**
     * Request status: one of the EveSrp\Type constants.
     *
     * @ORM\Column(type="string", length=16)
     * @var string
     * @see Type
     */
    private $status = '';
    
    public function __construct()
    {
        $this->actions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreated(): DateTime
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

    public function getSubmitter(): User
    {
        return $this->submitter;
    }

    public function setSubmitter(User $submitter): self
    {
        $this->submitter = $submitter;

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions->toArray();
    }

    public function getPilot(): Character
    {
        return $this->pilot;
    }

    public function setPilot(Character $pilot): self
    {
        $this->pilot = $pilot;

        return $this;
    }

    public function getCorporation(): ?string
    {
        return $this->corporation;
    }

    public function setCorporation(?string $corporation): self
    {
        $this->corporation = $corporation;

        return $this;
    }

    public function getAlliance(): ?string
    {
        return $this->alliance;
    }

    public function setAlliance(?string $alliance): self
    {
        $this->alliance = $alliance;

        return $this;
    }

    public function getShip(): string
    {
        return $this->ship;
    }

    public function setShip(string $ship): self
    {
        $this->ship = $ship;

        return $this;
    }

    public function getKillTime(): DateTime
    {
        return $this->killTime;
    }

    public function setKillTime(DateTime $dateTime): self
    {
        $this->killTime = $dateTime;

        return $this;
    }

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

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getBasePayout(): ?int
    {
        return $this->basePayout;
    }

    public function setBasePayout(?int $basePayout): self
    {
        $this->basePayout = $basePayout;

        return $this;
    }

    public function getPayout(): ?int
    {
        return $this->payout;
    }

    public function setPayout(?int $payout): self
    {
        $this->payout = $payout;

        return $this;
    }

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
