<?php

namespace App\Entity;

use App\Repository\RoommembersRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Rooms;
use App\Entity\Users;

/**
 * @ORM\Entity(repositoryClass=RoommembersRepository::class)
 */
class Roommembers
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
    private $roomid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userid;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomid(): ?string
    {
        return $this->roomid;
    }

    public function setRoomid(string $roomid): self
    {
        $this->roomid = $roomid;

        return $this;
    }

    public function getUserid(): ?string
    {
        return $this->userid;
    }

    public function setUserid(string $userid): self
    {
        $this->userid = $userid;

        return $this;
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
}
