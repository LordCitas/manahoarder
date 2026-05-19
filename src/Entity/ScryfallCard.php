<?php

namespace App\Entity;

use App\Repository\ScryfallCardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScryfallCardRepository::class)]
class ScryfallCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $scryfallId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $manaCost = null;

    #[ORM\Column(length: 500)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cardText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cardSet = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, UserCard>
     */
    #[ORM\OneToMany(targetEntity: UserCard::class, mappedBy: 'scryfallCard')]
    private Collection $userCards;

    public function __construct()
    {
        $this->userCards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScryfallId(): ?string
    {
        return $this->scryfallId;
    }

    public function setScryfallId(string $scryfallId): static
    {
        $this->scryfallId = $scryfallId;

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

    public function getManaCost(): ?string
    {
        return $this->manaCost;
    }

    public function setManaCost(string $manaCost): static
    {
        $this->manaCost = $manaCost;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCardText(): ?string
    {
        return $this->cardText;
    }

    public function setCardText(?string $cardText): static
    {
        $this->cardText = $cardText;

        return $this;
    }

    public function getCardSet(): ?string
    {
        return $this->cardSet;
    }

    public function setCardSet(?string $cardSet): static
    {
        $this->cardSet = $cardSet;

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
     * @return Collection<int, UserCard>
     */
    public function getUserCards(): Collection
    {
        return $this->userCards;
    }

    public function addUserCard(UserCard $userCard): static
    {
        if (!$this->userCards->contains($userCard)) {
            $this->userCards->add($userCard);
            $userCard->setScryfallCard($this);
        }

        return $this;
    }

    public function removeUserCard(UserCard $userCard): static
    {
        if ($this->userCards->removeElement($userCard)) {
            // set the owning side to null (unless already changed)
            if ($userCard->getScryfallCard() === $this) {
                $userCard->setScryfallCard(null);
            }
        }

        return $this;
    }
}
