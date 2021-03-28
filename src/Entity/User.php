<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @UniqueEntity(fields={"email"},message="Cette adresse mail est déjà utilisée")
 * @UniqueEntity(fields={"username"},message="Ce pseudo est déjà utilisé")
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"user:read"}}
 * )
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *          "username"="start"
 *     }
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=30)
     * @Groups({"user:read"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $lastname;

    /**
     * @ORM\Column(type="date")
     */
    private $birthday;

    /**
     * @ORM\OneToMany(targetEntity=Game::class, mappedBy="user1")
     */
    private $games1;

    /**
     * @ORM\OneToMany(targetEntity=Game::class, mappedBy="user2")
     */
    private $games2;

    /**
     * @ORM\OneToMany(targetEntity=Game::class, mappedBy="winner")
     */
    private $winners;

    /**
     * @ORM\Column(type="boolean")
     */
    private $gameReady = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"user:read"})
     */
    private $username;

    /**
     * @ORM\OneToMany(targetEntity=Friendship::class, mappedBy="user1", orphanRemoval=true)
     */
    private $friendships;

    public function __construct()
    {
        $this->games1 = new ArrayCollection();
        $this->games2 = new ArrayCollection();
        $this->winners = new ArrayCollection();
        $this->friendships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
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

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstname;
    }

    public function setFirstName(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastname;
    }

    public function setLastName(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return Collection|Game[]
     */
    public function getGames1(): Collection
    {
        return $this->games1;
    }

    public function addGames1(Game $games1): self
    {
        if (!$this->games1->contains($games1)) {
            $this->games1[] = $games1;
            $games1->setUser1($this);
        }

        return $this;
    }

    public function removeGames1(Game $games1): self
    {
        if ($this->games1->removeElement($games1)) {
            // set the owning side to null (unless already changed)
            if ($games1->getUser1() === $this) {
                $games1->setUser1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Game[]
     */
    public function getGames2(): Collection
    {
        return $this->games2;
    }

    public function addGames2(Game $games2): self
    {
        if (!$this->games2->contains($games2)) {
            $this->games2[] = $games2;
            $games2->setUser2($this);
        }

        return $this;
    }

    public function removeGames2(Game $games2): self
    {
        if ($this->games2->removeElement($games2)) {
            // set the owning side to null (unless already changed)
            if ($games2->getUser2() === $this) {
                $games2->setUser2(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Game[]
     */
    public function getWinners(): Collection
    {
        return $this->winners;
    }

    public function addWinner(Game $winner): self
    {
        if (!$this->winners->contains($winner)) {
            $this->winners[] = $winner;
            $winner->setWinner($this);
        }

        return $this;
    }

    public function removeWinner(Game $winner): self
    {
        if ($this->winners->removeElement($winner)) {
            // set the owning side to null (unless already changed)
            if ($winner->getWinner() === $this) {
                $winner->setWinner(null);
            }
        }

        return $this;
    }

    public function display()
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function getGameReady(): ?bool
    {
        return $this->gameReady;
    }

    public function setGameReady(bool $gameReady): self
    {
        $this->gameReady = $gameReady | false;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection|Friendship[]
     */
    public function getFriendships(): Collection
    {
        return $this->friendships;
    }

    public function addFriendship(Friendship $friendship): self
    {
        if (!$this->friendships->contains($friendship)) {
            $this->friendships[] = $friendship;
            $friendship->setUser1($this);
        }

        return $this;
    }

    public function removeFriendship(Friendship $friendship): self
    {
        if ($this->friendships->removeElement($friendship)) {
            // set the owning side to null (unless already changed)
            if ($friendship->getUser1() === $this) {
                $friendship->setUser1(null);
            }
        }

        return $this;
    }
}
