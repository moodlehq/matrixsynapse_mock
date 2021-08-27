<?php

namespace App\Entity;

use App\Repository\AttendeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AttendeeRepository::class)
 */
class Attendee
{

    const ROLE_MODERATOR = 'MODERATOR';

    const ROLE_VIEWER = 'VIEWER';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userID;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $role = self::ROLE_VIEWER;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullName;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isListeningOnly = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasJoinedVoice = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasVideo = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $clientType = 'HTML5';

    /**
     * @ORM\Column(type="json")
     */
    private $customData = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPresenter = false;

    /**
     * @ORM\ManyToOne(targetEntity=Meeting::class, inversedBy="attendees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $meetingID;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverID;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserID(): ?string
    {
        return $this->userID;
    }

    public function setUserID(string $userID): self
    {
        $this->userID = $userID;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isParticipant(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getIsListeningOnly(): ?bool
    {
        return $this->isListeningOnly;
    }

    public function setIsListeningOnly(bool $isListeningOnly): self
    {
        $this->isListeningOnly = $isListeningOnly;

        return $this;
    }

    public function getHasJoinedVoice(): ?bool
    {
        return $this->hasJoinedVoice;
    }

    public function hasJoinedVoice(): bool
    {
        return $this->hasJoinedVoice;
    }


    public function setHasJoinedVoice(bool $hasJoinedVoice): self
    {
        $this->hasJoinedVoice = $hasJoinedVoice;

        return $this;
    }

    public function getHasVideo(): ?bool
    {
        return $this->hasVideo;
    }

    public function hasVideo(): bool
    {
        return $this->hasVideo;
    }

    public function setHasVideo(bool $hasVideo): self
    {
        $this->hasVideo = $hasVideo;

        return $this;
    }

    public function getClientType(): ?string
    {
        return $this->clientType;
    }

    public function setClientType(string $clientType): self
    {
        $this->clientType = $clientType;

        return $this;
    }

    public function getCustomData(): ?array
    {
        return $this->customData;
    }

    public function setCustomData(array $customData): self
    {
        $this->customData = $customData;

        return $this;
    }

    public function getIsPresenter(): ?bool
    {
        return $this->isPresenter;
    }

    public function setIsPresenter(bool $isPresenter): self
    {
        $this->isPresenter = $isPresenter;

        return $this;
    }

    public function getMeetingID(): ?Meeting
    {
        return $this->meetingID;
    }

    public function setMeetingID(?Meeting $meetingID): self
    {
        $this->meetingID = $meetingID;

        return $this;
    }

    public function getServerID(): ?string
    {
        return $this->serverID;
    }

    public function setServerID(string $serverID): self
    {
        $this->serverID = $serverID;

        return $this;
    }
}
