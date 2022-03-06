<?php

namespace App\Entity;

use App\Repository\RecordingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecordingRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Recording
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Meeting::class, inversedBy="recordings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $meeting;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $recordID;

    /**
     * @ORM\Column(type="boolean")
     */
    private $published = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $protected = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isBreakout = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startTime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $participants = 1;

    /**
     * @ORM\Column(type="json")
     */
    private $metadata = [];

    /**
     * @ORM\Column(type="json")
     */
    private $playback = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $headless = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $imported = false;

    /**
     * @ORM\Column(type="json")
     */
    private $recording = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $brokerNotified = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $serverID;

    public function __construct()
    {
        $this->startTime = new \DateTime();
    }

    public function getRecordingInfo(): array
    {
        $recordingInfo = [
            'meetingID' => $this->getMeeting()->getMeetingID(),
            'recordID' => $this->getRecordID(),
            'published' => $this->stringifyBool($this->published),
            'protected' => $this->stringifyBool($this->protected),
            'startTime' => $this->startTime->format('Uu') / 1000,
            'endTime' => $this->endTime->format('Uu') / 1000,
            'participants' => $this->participants,
            'playback' => $this->getPlayback(),
            'metadata' => $this->getMetadata(),
            'isBreakout' => $this->isBreakout()
        ];
        if ($this->getMeeting()->hasSubMeetings()) {
            $breakoutRooms = [];
            foreach($this->getMeeting()->getChildMeetings() as $childMeeting) {
                foreach($childMeeting->getRecordings() as $childRecording) {
                    $breakoutRooms[] = $childRecording->getRecordID();
                }
            }
            $recordingInfo['breakoutRooms'] = (object) [
                'forcexmlarraytype' => 'breakoutRoom',
                'array' => $breakoutRooms,
            ];
        }
        return $recordingInfo;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(?Meeting $meeting): self
    {
        $this->meeting = $meeting;

        return $this;
    }

    public function getRecordID(): ?string
    {
        return $this->recordID;
    }

    public function setRecordID(string $recordID): self
    {
        $this->recordID = $recordID;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setRecordIDFromMeeting(): void
    {
        $seed = $this->getMeeting()->getMeetingID();
        if ($this->getMeeting()->isBreakout()) {
            $seed .= $this->getMeeting()->getBreakoutSequence();
        }
        $this->recordID = sprintf(
            "%s-%s",
            md5($seed),
            time() + random_int(0, PHP_INT_MAX/2)
        );
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function getProtected(): ?bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected): self
    {
        $this->protected = $protected;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setPrePersistStartTime(): void
    {
        if (empty($this->startTime)) {
            $this->startTime = new \DateTime();

            $interval = 3600 * rand(1, 15) * 24;
            $this->startTime->subtract(new \DateInterval("PT{$interval}S"));
        }
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setPrePersistEndTime(): void
    {
        if (empty($this->endTime)) {
            $this->endTime = clone $this->startTime;

            $interval = 3600 * rand(1, 15) * 24;
            $this->endTime->add(new \DateInterval("PT{$interval}S"));
        }
    }

    public function getParticipants(): ?int
    {
        return $this->participants;
    }

    public function setParticipants(int $participants): self
    {
        $this->participants = $participants;

        return $this;
    }

    public function getMetadata(): ?array
    {
        $metadata = array_merge($this->metadata, []);
        $metadata['isBreakout'] = $this->stringifyBool(!empty($metadata['isBreakout']));

        return $metadata;
    }

    public function getMetadataValue(string $key)
    {
        $metadata = $this->getMetadata();
        if (array_key_exists($key, $metadata)) {
            return $metadata[$key];
        }

        return null;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getPlayback(): ?array
    {
        return $this->playback;
    }

    /**
     * @ORM\PrePersist
     */
    public function calculatePlayback(): void
    {
        if (empty($this->playback)) {
            error_log(var_export($this->getEndTime()->diff($this->getStartTime()), true));
            $this->playback = [
                'format' => [
                    'type' => 'presentation',
                    'url' => '',
                    'length' => $this->getEndTime()->getTimestamp() - $this->getStartTime()->getTimestamp(),
                ],
            ];
        }
    }

    public function setPlayback(array $playback): self
    {
        $this->playback = $playback;

        return $this;
    }

    public function isBreakout(): ?bool
    {
        return $this->getMeeting()->isBreakout();
    }

    public function isHeadless(): ?bool
    {
        return $this->headless;
    }

    public function setHeadless(bool $headless): self
    {
        $this->headless = $headless;

        return $this;
    }

    public function isImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(bool $imported): self
    {
        $this->imported = $imported;

        return $this;
    }

    public function getRecording(): ?array
    {
        return $this->recording;
    }

    public function setRecording(array $recording): self
    {
        $this->recording = $recording;

        return $this;
    }

    protected function stringifyBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    public function getBrokerNotified(): ?bool
    {
        return $this->brokerNotified;
    }

    public function setBrokerNotified(bool $brokerNotified): self
    {
        $this->brokerNotified = $brokerNotified;

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
