<?php

namespace App\Entity;

use App\Repository\UserCardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCardRepository::class)]
class UserCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userCards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userCards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ScryfallCard $scryfallCard = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?bool $isFoil = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAdded = null;

    #[ORM\ManyToOne(inversedBy: 'userCards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function isFoil(): ?bool
    {
        return $this->isFoil;
    }

    public function setIsFoil(bool $isFoil): static
    {
        $this->isFoil = $isFoil;

        return $this;
    }

    public function getDateAdded(): ?\DateTimeImmutable
    {
        return $this->dateAdded;
    }

    public function setDateAdded(\DateTimeImmutable $dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }
}
