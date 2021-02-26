<?php

namespace App\Entity;

use App\Repository\SetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SetRepository::class)
 */
class Round
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ended;

    /**
     * @ORM\ManyToOne(targetEntity=Game::class, inversedBy="sets")
     */
    private $game;

    /**
     * @ORM\Column(type="array")
     */
    private $user1_board_cards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $user2_board_cards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $board = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $removed_card;

    /**
     * @ORM\Column(type="array")
     */
    private $user1_action = [];

    /**
     * @ORM\Column(type="array")
     */
    private $user2_action = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $set_number;

    /**
     * @ORM\Column(type="array")
     */
    private $user1_hand_cards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $user2_hand_cards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $pioche = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getEnded(): ?\DateTimeInterface
    {
        return $this->ended;
    }

    public function setEnded(?\DateTimeInterface $ended): self
    {
        $this->ended = $ended;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getUser1BoardCards(): ?array
    {
        return $this->user1_board_cards;
    }

    public function setUser1BoardCards(array $user1_board_cards): self
    {
        $this->user1_board_cards = $user1_board_cards;

        return $this;
    }

    public function getUser2BoardCards(): ?array
    {
        return $this->user2_board_cards;
    }

    public function setUser2BoardCards(array $user2_board_cards): self
    {
        $this->user2_board_cards = $user2_board_cards;

        return $this;
    }

    public function getBoard(): ?array
    {
        return $this->board;
    }

    public function setBoard(array $board): self
    {
        $this->board = $board;

        return $this;
    }

    public function getRemovedCard(): ?int
    {
        return $this->removed_card;
    }

    public function setRemovedCard(int $removed_card): self
    {
        $this->removed_card = $removed_card;

        return $this;
    }

    public function getUser1Action(): ?array
    {
        return $this->user1_action;
    }

    public function setUser1Action(array $user1_action): self
    {
        $this->user1_action = $user1_action;

        return $this;
    }

    public function getUser2Action(): ?array
    {
        return $this->user2_action;
    }

    public function setUser2Action(array $user2_action): self
    {
        $this->user2_action = $user2_action;

        return $this;
    }

    public function getSetNumber(): ?int
    {
        return $this->set_number;
    }

    public function setSetNumber(int $set_number): self
    {
        $this->set_number = $set_number;

        return $this;
    }

    public function getUser1HandCards(): ?array
    {
        return $this->user1_hand_cards;
    }

    public function setUser1HandCards(array $user1_hand_cards): self
    {
        $this->user1_hand_cards = $user1_hand_cards;

        return $this;
    }

    public function getUser2HandCards(): ?array
    {
        return $this->user2_hand_cards;
    }

    public function setUser2HandCards(array $user2_hand_cards): self
    {
        $this->user2_hand_cards = $user2_hand_cards;

        return $this;
    }

    public function getPioche(): ?array
    {
        return $this->pioche;
    }

    public function setPioche(array $pioche): self
    {
        $this->pioche = $pioche;

        return $this;
    }
}
