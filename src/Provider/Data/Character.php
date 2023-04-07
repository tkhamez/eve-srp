<?php

declare(strict_types=1);

namespace EveSrp\Provider\Data;

class Character
{
    public function __construct(
        /**
         * EVE character ID.
         */
        private int $id,

        /**
         * EVE character name.
         */
        private string $name,

        /**
         * Optionally specifies whether this character is the main character of the account.
         */
        private ?bool $main = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMain(): ?bool
    {
        return $this->main;
    }
}
