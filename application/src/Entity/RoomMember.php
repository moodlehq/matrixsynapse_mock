<?php

namespace App\Entity;

use App\Repository\RoomMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Room;
use App\Entity\Users;

/**
 * @ORM\Entity(repositoryClass=RoomMemberRepository::class)
 */
class RoomMember
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverid;

    /**
     * @ORM\ManyToOne(targetEntity=Room::class, inversedBy="members")
     * @ORM\JoinColumn(nullable=false)
     */
    private $room;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="rooms")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reason;

    /**
     * @ORM\Column(type="boolean")
     */
    private $accepted;

    /**
     * @ORM\Column(type="boolean")
     */
    private $banned;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $state;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServerid(): ?string
    {
        return $this->serverid;
    }

    public function setServerid(string $serverid): self
    {
        $this->serverid = $serverid;

        return $this;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getRoomid(): ?string
    {
        return $this->getRoom()->getRoomid();
    }

    public function getUser(): Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserid(): ?string
    {
        return $this->getUser()->getUserid();
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getAccepted(): ?bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted = false): self
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getBanned(): ?bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned = false): self
    {
        $this->banned = $banned;

        return $this;
    }

    public function getState(): ?bool
    {
        return $this->state;
    }

    public function setState(string $state = null): self
    {
        $this->state = $state;

        return $this;
    }
}
