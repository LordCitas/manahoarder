<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, TournamentParticipant>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipant::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $tournamentParticipants;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $format = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column]
    private ?int $maxPlayers = null;

    #[ORM\Column(length: 8)]
    private ?string $inviteCode = null;

    #[ORM\ManyToOne(inversedBy: 'tournaments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentRound = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $artUrl = null;

    public function __construct()
    {
        $this->tournamentParticipants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $tournamentParticipant->setTournament($this);
        }

        return $this;
    }

    public function removeTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if ($this->tournamentParticipants->removeElement($tournamentParticipant)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipant->getTournament() === $this) {
                $tournamentParticipant->setTournament(null);
            }
        }

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(string $inviteCode): static
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

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

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getCurrentRound(): ?int
    {
        return $this->currentRound;
    }

    public function setCurrentRound(?int $currentRound): static
    {
        $this->currentRound = $currentRound;

        return $this;
    }

    public function getArtUrl(): ?string
    {
        return $this->artUrl;
    }

    public function setArtUrl(?string $artUrl): static
    {
        $this->artUrl = $artUrl;

        return $this;
    }
}
