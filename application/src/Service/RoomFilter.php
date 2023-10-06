<?php

namespace App\Service\Filter;

class RoomFilter
{
    private array $roomIds = [];
    private StateFilter $roomStateFilter;

    public static function fromObject(\stdClass $filterData): static
    {
        $filter = new static();

        // Check for a list of rooms.
        if (property_exists($filterData, 'rooms')) {
            $rooms = (array) $filterData->rooms;
            $filter->setRoomIds($rooms);
        }

        // Check for a room state filter.
        if (property_exists($filterData, 'state')) {
            $filter->setRoomState(StateFilter::fromObject($filterData->state));
        }

        return $filter;
    }

    public function setRoomIds(array $ids): static
    {
        $this->roomIds = $ids;
        return $this;
    }

    public function setRoomState(StateFilter $state): static
    {
        $this->roomStateFilter = $state;
        return $this;
    }

    public function getRoomIds(): array
    {
        return $this->roomIds;
    }

    public function getRoomState(): StateFilter
    {
        return $this->roomStateFilter;
    }
}
