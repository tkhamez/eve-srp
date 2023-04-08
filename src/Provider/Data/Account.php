<?php

declare(strict_types=1);

namespace EveSrp\Provider\Data;

class Account
{
    /**
     * @var Character[]
     */
    private array $characters = [];

    public function __construct(
        /**
         * The account identifier of the external account to which all the characters belong.
         *
         * Must be set or characters cannot be synced.
         * Must not be longer than 255 characters.
         */
        private string $id
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addCharacter(Character $character): void
    {
        $this->characters[] = $character;
    }

    /**
     * @return Character[]
     */
    public function getCharacters(): array
    {
        return $this->characters;
    }
}
