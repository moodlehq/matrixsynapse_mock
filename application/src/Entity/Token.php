<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $accesstoken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $refreshtoken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $expiresinms;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $serverid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserid(): ?User
    {
        return $this->userid;
    }

    public function setUserid(?User $userid): self
    {
        $this->userid = $userid;

        return $this;
    }

    public function getAccesstoken(): ?string
    {
        return $this->accesstoken;
    }

    public function setAccesstoken(string $accesstoken): self
    {
        $this->accesstoken = $accesstoken;

        return $this;
    }

    public function getRefreshtoken(): ?string
    {
        return $this->refreshtoken;
    }

    public function setRefreshtoken(string $refreshtoken): self
    {
        $this->refreshtoken = $refreshtoken;

        return $this;
    }

    public function getExpiresinms(): ?string
    {
        return $this->expiresinms;
    }

    public function setExpiresinms(string $expiresinms = null): self
    {
        $this->expiresinms = $expiresinms;

        return $this;
    }

    public function getServerid(): ?string
    {
        return $this->serverid;
    }

    public function setServerid(string $serverid = null): self
    {
        $this->serverid = $serverid;

        return $this;
    }
}
