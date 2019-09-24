<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Brave\EveSrp\Type;
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
     * @ORM\Column(type="string", length=255)
     */
    private $corporation;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $alliance;

    /**
     * @var string
     * @ORM\Column(type="string", length=128)
     */
    private $ship;

    /**
     * @ORM\Column(type="datetime", name="kill_time")
     * @var DateTime
     */
    private $killTime;

    /**
     * @var string
     * @ORM\Column(type="string", name="solar_system", length=32)
     */
    private $solarSystem;

    /**
     * @var string
     * @ORM\Column(type="string", name="kill_mail", length=512)
     */
    private $killMail;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     * @var string
     */
    private $details;

    /**
     * @ORM\Column(type="integer", name="base_payout")
     * @var integer
     */
    private $basePayout;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $payout;

    /**
     * Request status: one of the Brave\EveSrp\Type constants.
     *
     * @ORM\Column(type="string", length=16)
     * @var string
     * @see Type
     */
    private $status;
    
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

    public function getDivision(): Division
    {
        return $this->division;
    }

    public function getSubmitter(): User
    {
        return $this->submitter;
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

    public function getCorporation(): string
    {
        return $this->corporation;
    }

    public function getAlliance(): string
    {
        return $this->alliance;
    }

    public function getShip(): string
    {
        return $this->ship;
    }

    public function getKillTime(): DateTime
    {
        return $this->killTime;
    }

    public function getSolarSystem(): string
    {
        return $this->solarSystem;
    }

    public function getKillMail(): string
    {
        return $this->killMail;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function getBasePayout(): int
    {
        return $this->basePayout;
    }

    public function getPayout(): int
    {
        return $this->payout;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
