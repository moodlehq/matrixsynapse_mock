<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UsersRepository::class)
 */
class Users
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
    private $userid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $displayname;

    /**
     * @ORM\OneToMany(targetEntity=Threepids::class, mappedBy="userid")
     */
    private $threepids;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverid;

    /**
     * @ORM\OneToMany(targetEntity=Externalids::class, mappedBy="userid")
     */
    private $externalids;

    /**
     * @ORM\Column(type="boolean")
     */
    private $admin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $passwordpattern;

    /**
     * @ORM\OneToMany(targetEntity=Tokens::class, mappedBy="userid")
     */
    private $tokens;

    /**
     * @ORM\OneToMany(targetEntity=Passwords::class, mappedBy="userid")
     */
    private $passwords;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $avatarurl;

    public function __construct()
    {
        $this->threepids = new ArrayCollection();
        $this->externalids = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->passwords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDisplayname(): ?string
    {
        return $this->displayname;
    }

    public function setDisplayname(string $displayname): self
    {
        $this->displayname = $displayname;

        return $this;
    }

    /**
     * @return Collection<int, Threepids>
     */
    public function getThreepids(): Collection
    {
        return $this->threepids;
    }

    public function addThreepid(Threepids $threepid): self
    {
        if (!$this->threepids->contains($threepid)) {
            $this->threepids[] = $threepid;
            $threepid->setUserid($this);
        }

        return $this;
    }

    public function removeThreepid(Threepids $threepid): self
    {
        if ($this->threepids->removeElement($threepid)) {
            // set the owning side to null (unless already changed)
            if ($threepid->getUserid() === $this) {
                $threepid->setUserid(null);
            }
        }

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

    public function getAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin = false): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getPasswordpattern(): ?string
    {
        return $this->passwordpattern;
    }

    public function setPasswordpattern(string $passwordpattern = null): self
    {
        $this->passwordpattern = $passwordpattern;

        return $this;
    }

    public function getAvatarurl(): ?string
    {
        return $this->avatarurl;
    }

    public function setAvatarurl(string $avatarurl = null): self
    {
        $this->avatarurl = $avatarurl;

        return $this;
    }

    /**
     * @return Collection<int, Externalids>
     */
    public function getExternalids(): Collection
    {
        return $this->externalids;
    }

    public function addExternalid(Externalids $externalid): self
    {
        if (!$this->externalids->contains($externalid)) {
            $this->externalids[] = $externalid;
            $externalid->setUserid($this);
        }

        return $this;
    }

    public function removeExternalid(Externalids $externalid): self
    {
        if ($this->externalids->removeElement($externalid)) {
            // set the owning side to null (unless already changed)
            if ($externalid->getUserid() === $this) {
                $externalid->setUserid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<Tokens>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(Tokens $token): self
    {
        $token->setUserid($this);
        $this->tokens->add($token);

        return $this;
    }

    /**
     * @return Collection<Passwords>
     */
    public function getPasswords(): Collection
    {
        return $this->passwords;
    }

    public function addPasswords(Passwords $password): self
    {
        $password->setUserid($this);
        $this->passwords->add($password);

        return $this;
    }
}
