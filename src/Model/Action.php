<?php

declare(strict_types=1);

namespace EveSrp\Model;

use EveSrp\Type;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "actions", options: ["charset" => "utf8mb4", "collate" => "utf8mb4_unicode_520_ci"])]
class Action
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private ?DateTime $created = null;

    /**
     * Action category/type: one of the EveSrp\Type constants.
     *
     * @see Type
     */
    #[ORM\Column(type: "string", length: 16)]
    private ?string $category = null;

    #[ORM\ManyToOne(targetEntity: "User")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: "Request", inversedBy: "actions")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Request $request = null;

    #[ORM\Column(type: "text", length: 16777215, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreated(?DateTime $created): void
    {
        $this->created = $created;
    }

    /** @noinspection PhpUnused */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    /** @noinspection PhpUnused */
    public function getCategory(): ?string
    {
        return $this->category;
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

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
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
}
