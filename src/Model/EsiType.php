<?php

declare(strict_types=1);

namespace EveSrp\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="esi_types")
 */
class EsiType
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $name = '';

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id !== null ? (int)$this->id : null;
    }

    public function setName(string $name): self
    {
        $this->name = mb_substr($name, 0, 255);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
