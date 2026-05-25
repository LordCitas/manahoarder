<?php

namespace App\Entity;

use App\Repository\DeckCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeckCardRepository::class)]
class DeckCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deckCards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Decklist $decklist = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ScryfallCard $scryfallCard = null;

    #[ORM\Column]
    private ?int $quantity = 1;

    #[ORM\Column]
    private bool $isSideboard = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDecklist(): ?Decklist
    {
        return $this->decklist;
    }

    public function setDecklist(?Decklist $decklist): static
    {
        $this->decklist = $decklist;

        return $this;
    }

    public function getScryfallCard(): ?ScryfallCard
    {
        return $this->scryfallCard;
    }

    public function setScryfallCard(?ScryfallCard $scryfallCard): static
    {
        $this->scryfallCard = $scryfallCard;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isSideboard(): bool
    {
        return $this->isSideboard;
    }

    public function setIsSideboard(bool $isSideboard): static
    {
        $this->isSideboard = $isSideboard;

        return $this;
    }
}
