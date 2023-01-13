<?php

namespace App\Entity;

use App\Repository\ExternalIdsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ExternalIdsRepository::class)
 */
class ExternalIds
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
    private $authprovider;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $externalid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthprovider(): ?string
    {
        return $this->authprovider;
    }

    public function setAuthprovider(string $authprovider): self
    {
        $this->authprovider = $authprovider;

        return $this;
    }

    public function getExternalid(): ?string
    {
        return $this->externalid;
    }

    public function setExternalid(string $externalid): self
    {
        $this->externalid = $externalid;

        return $this;
    }
}
