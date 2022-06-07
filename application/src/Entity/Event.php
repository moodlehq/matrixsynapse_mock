<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * Event typpes
     */
    const TYPE_JOIN = 'join';
    const TYPE_LEAVE = 'leaves';
    const TYPE_CHAT = 'chats';
    const TYPE_TALK = 'talks';
    const TYPE_EMOJIS = 'emojis';
    const TYPE_RAISEHAND = 'raisehand';
    const TYPE_POLL_VOTE = 'poll_vote';
    const TYPE_TALK_TIME = 'talk_time';
    const TYPE_ATTENDANCE = 'attendance'; // This is not a real type, we added it to simulate duration.

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createTime;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type = '';

    /**
     * @ORM\Column(type="string", length=2048)
     */
    private $data = '';


    /**
     * @ORM\ManyToOne(targetEntity=Attendee::class)
     */
    private $attendee;

    /**
     * @ORM\ManyToOne(targetEntity=Meeting::class, inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $meeting;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverID;


    public function __construct(Attendee $attendee, string $type, string $data = '')
    {
        $this->createTime = new \DateTime();
        $this->attendee = $attendee;
        $this->type = $type;
        $this->data = $data;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttendee(): Attendee
    {
        return $this->attendee;
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(?Meeting $meeting): self
    {
        $this->meeting = $meeting;
        $this->serverID = $meeting->getServerID();

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

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
