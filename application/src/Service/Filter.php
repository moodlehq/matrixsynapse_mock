<?php

namespace App\Service;

use App\Service\Filter\RoomFilter;
use Symfony\Component\HttpFoundation\Request;


class Filter
{
    private array $roomIds = [];
    private RoomFilter $roomFilter;

    public static function fromRequest(Request $request): static
    {
        $filter = new static();

        $filterData = $request->query->get('filter');
        if (!$filterData) {
            return $filter;
        }

        // Filter the event data according to the API:
        // https://spec.matrix.org/v1.1/client-server-api/#filtering
        // Filters are made up of different filter types.
        // Detailed here: https://spec.matrix.org/v1.1/client-server-api/#post_matrixclientv3useruseridfilter
        $filterData = json_decode($request->query->get('filter'));

        if (property_exists($filterData, 'room')) {
            $filter->setRoomFilter(RoomFilter::fromObject($filterData->room));
        }

        return $filter;
    }

    public function setRoomFilter(RoomFilter $roomFilter): static
    {
        $this->roomFilter = $roomFilter;
        return $this;
    }

    public function getRequestedRoomIds(): array
    {
        return $this->roomIds;
    }

    public function getRoomFilter(): RoomFilter
    {
        if (empty($this->roomFilter)) {
            $this->roomFilter = new RoomFilter();
        }

        return $this->roomFilter;
    }
}
