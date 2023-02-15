<?php

namespace App\Entity;

use App\Repository\RoomsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Roommembers;

/**
 * @ORM\Entity(repositoryClass=RoomsRepository::class)
 */
class Rooms
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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $topic;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $roomid;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $roomalias;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $creator;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): self
    {
        $this->topic = $topic;

        return $this;
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

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getRoomAlias(): ?string
    {
        return $this->roomalias;
    }

    public function setRoomAlias(?string $roomalias): self
    {
        $this->roomalias = $roomalias;

        return $this;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(?string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }
}
