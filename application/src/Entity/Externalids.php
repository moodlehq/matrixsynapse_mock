<?php

namespace App\Entity;

use App\Repository\ExternalidsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ExternalidsRepository::class)
 */
class Externalids
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
    private $auth_provider;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $external_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverid;

    /**
     * @ORM\ManyToOne(targetEntity=Users::class, inversedBy="externalids")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthProvider(): ?string
    {
        return $this->auth_provider;
    }

    public function setAuthProvider(string $auth_provider): self
    {
        $this->auth_provider = $auth_provider;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function setExternalId(string $external_id): self
    {
        $this->external_id = $external_id;

        return $this;
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

    public function getUserid(): ?Users
    {
        return $this->userid;
    }

    public function setUserid(?Users $userid): self
    {
        $this->userid = $userid;

        return $this;
    }
}
