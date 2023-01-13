<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ThreepidsRepository::class)
 */
class Threepids
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @ORM\ManyToOne(targetEntity=user::class, inversedBy="threepids")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $medium;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;


    /**
     * @ORM\ManyToOne(targetEntity=Meeting::class, inversedBy="attendees")
     * @ORM\JoinColumn(nullable=false)
     */
    //private $meeting;

    public function getMedium(): ?string
    {
        return $this->medium;
    }

    public function setMedium(string $medium): self
    {
        $this->medium = $medium;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddresse(string $address): self
    {
        $this->address = $address;

        return $this;
    }

}
