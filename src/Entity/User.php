<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_NICKNAME', fields: ['nickname'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['nickname'], message: 'This nickname is already taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $profilePictureFilename = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $profileArtUrl = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, UserCard>
     */
    #[ORM\OneToMany(targetEntity: UserCard::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userCards;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $albums;

    /**
     * @var Collection<int, Decklist>
     */
    #[ORM\OneToMany(targetEntity: Decklist::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $decklists;

    /**
     * @var Collection<int, TournamentParticipant>
     */
    #[ORM\OneToMany(targetEntity: TournamentParticipant::class, mappedBy: 'user')]
    private Collection $tournamentParticipants;

    /**
     * @var Collection<int, Tournament>
     */
    #[ORM\OneToMany(targetEntity: Tournament::class, mappedBy: 'creator', orphanRemoval: true)]
    private Collection $tournaments;

    public function __construct()
    {
        $this->userCards = new ArrayCollection();
        $this->albums = new ArrayCollection();
        $this->decklists = new ArrayCollection();
        $this->tournamentParticipants = new ArrayCollection();
        $this->tournaments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getProfilePictureFilename(): ?string
    {
        return $this->profilePictureFilename;
    }

    public function setProfilePictureFilename(?string $profilePictureFilename): static
    {
        $this->profilePictureFilename = $profilePictureFilename;

        return $this;
    }

    public function getProfileArtUrl(): ?string
    {
        return $this->profileArtUrl;
    }

    public function setProfileArtUrl(?string $profileArtUrl): static
    {
        $this->profileArtUrl = $profileArtUrl;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->nickname;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
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
            $userCard->setUser($this);
        }

        return $this;
    }

    public function removeUserCard(UserCard $userCard): static
    {
        if ($this->userCards->removeElement($userCard)) {
            // set the owning side to null (unless already changed)
            if ($userCard->getUser() === $this) {
                $userCard->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setUser($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getUser() === $this) {
                $album->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Decklist>
     */
    public function getDecklists(): Collection
    {
        return $this->decklists;
    }

    public function addDecklist(Decklist $decklist): static
    {
        if (!$this->decklists->contains($decklist)) {
            $this->decklists->add($decklist);
            $decklist->setUser($this);
        }

        return $this;
    }

    public function removeDecklist(Decklist $decklist): static
    {
        if ($this->decklists->removeElement($decklist)) {
            // set the owning side to null (unless already changed)
            if ($decklist->getUser() === $this) {
                $decklist->setUser(null);
            }
        }

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
            $tournamentParticipant->setUser($this);
        }

        return $this;
    }

    public function removeTournamentParticipant(TournamentParticipant $tournamentParticipant): static
    {
        if ($this->tournamentParticipants->removeElement($tournamentParticipant)) {
            // set the owning side to null (unless already changed)
            if ($tournamentParticipant->getUser() === $this) {
                $tournamentParticipant->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tournament>
     */
    public function getTournaments(): Collection
    {
        return $this->tournaments;
    }

    public function addTournament(Tournament $tournament): static
    {
        if (!$this->tournaments->contains($tournament)) {
            $this->tournaments->add($tournament);
            $tournament->setCreator($this);
        }

        return $this;
    }

    public function removeTournament(Tournament $tournament): static
    {
        if ($this->tournaments->removeElement($tournament)) {
            // set the owning side to null (unless already changed)
            if ($tournament->getCreator() === $this) {
                $tournament->setCreator(null);
            }
        }

        return $this;
    }
}
