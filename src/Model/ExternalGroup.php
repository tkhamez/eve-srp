<?php

declare(strict_types=1);

namespace Brave\EveSrp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="external_groups")
 */
class ExternalGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private $name = '';

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="externalGroups")
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }
}
