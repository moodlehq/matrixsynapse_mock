<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\RoomMember;

/**
 * @ORM\Entity(repositoryClass=RoomRepository::class)
 */
class Room
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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $space;

    /**
     * @ORM\OneToMany(targetEntity=RoomMember::class, mappedBy="room", cascade={"persist", "remove"})
     */
    private Collection $members;

    public function jsonSerialize(): \stdClass
    {
        return (object) [
            'id' => $this->id,
            'serverid' => $this->serverid,
            'name' => $this->name,
            'topic' => $this->topic,
            'room_id' => $this->roomid,
            'avatar' => $this->avatar,
            'roomalias' => $this->roomalias,
            'creator' => $this->creator,
            'space' => $this->space,
        ];
    }

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

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

    public function getSpace(): ?string
    {
        return $this->space;
    }

    public function setSpace(?string $space): self
    {
        $this->space = $space;

        return $this;
    }

    public function addMember(User $user): self
    {
        $roomMember = new RoomMember();
        $roomMember->setRoom($this);
        $roomMember->setUser($user);
        $this->members->add($user);

        return $this;
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function getRoomState(): array
    {
        // We will probably need to pass a filter in the future.
        // At the moment we only know how to serve a set of power levels.
        $roomData = [
            'state' => [
                'events' => [],
            ],
        ];

        $roomData['state']['events'][] = $this->getPowerLevelState();

        return $roomData;
    }

    protected function getPowerLevelState(): array
    {
        // Power level values.
        $powerLevelDefault = 0;
        $powerLevelModerator = 50;
        $powerLevelMaximum = 100;

        // Get the members of the room who have a powerlevel set.
        $members = $this->getMembers()->filter(function (RoomMember $member) {
            return $member->getPowerLevel() !== null;
        });

        // Build the power level state.
        $memberInfo = $members->map(fn(RoomMember $member) => [
            $member->getUser()->getUserid() => $member->getPowerLevel(),
        ])->toArray();

        return [
            'type' => 'm.room.power_levels',
            'content' => [
                'users' => (object) $memberInfo,
                'users_default' => $powerLevelDefault,
                'events' => [
                    'm.room.name' => $powerLevelModerator,
                    'm.room.power_levels' => $powerLevelMaximum,
                    'm.room.history_visibility' => $powerLevelMaximum,
                    'm.room.canonical_alias' => $powerLevelModerator,
                    'm.room.avatar' => $powerLevelModerator,
                    'm.room.tombstone' => $powerLevelMaximum,
                    'm.room.server_acl' => $powerLevelMaximum,
                    'm.room.encryption' => $powerLevelMaximum,
                ],
                'events_default' =>  $powerLevelDefault,
                'state_default' => $powerLevelModerator,
                'ban' => $powerLevelModerator,
                'kick' => $powerLevelModerator,
                'redact' => $powerLevelModerator,
                'invite' =>  $powerLevelDefault,
                'historical' => $powerLevelMaximum,
            ],
        ];
    }
}
