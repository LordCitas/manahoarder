<?php

namespace App\Entity;

use App\Repository\DecklistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DecklistRepository::class)]
class Decklist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $format = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'decklists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, TournamentParticipant>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipant::class, mappedBy: 'decklist')]
    private Collection $tournamentParticipants;

    /**
     * @var Collection<int, DeckCard>
     */
    #[ORM\OneToMany(targetEntity: DeckCard::class, mappedBy: 'decklist', cascade: ['persist', 'remove'])]
    private Collection $deckCards;

    public function __construct()
    {
        $this->tournamentParticipants = new ArrayCollection();
        $this->deckCards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, TournamentParticipant>
     */
    public function getTournamentParticipants(): Collection
    {
        return $this->tournamentParticipants;
    }

    public function addTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if (!$this->tournamentParticipants->contains($tournamentParticipant)) {
            $this->tournamentParticipants->add($tournamentParticipant);
            $tournamentParticipant->setDecklist($this);
        }

        return $this;
    }

    public function removeTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if ($this->tournamentParticipants->removeElement($tournamentParticipant)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipant->getDecklist() === $this) {
                $tournamentParticipant->setDecklist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DeckCard>
     */
    public function getDeckCards(): Collection
    {
        return $this->deckCards;
    }

    public function addDeckCard(DeckCard $deckCard): static
    {
        if (!$this->deckCards->contains($deckCard)) {
            $this->deckCards->add($deckCard);
            $deckCard->setDecklist($this);
        }

        return $this;
    }

    public function removeDeckCard(DeckCard $deckCard): static
    {
        if ($this->deckCards->removeElement($deckCard)) {
            // set the owning side to null (unless already changed)
            if ($deckCard->getDecklist() === $this) {
                $deckCard->setDecklist(null);
            }
        }

        return $this;
    }
}
