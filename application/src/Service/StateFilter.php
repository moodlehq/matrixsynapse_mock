<?php

namespace App\Service\Filter;

class StateFilter
{
    private array $types = [];

    public static function fromObject(\stdClass $filterData): static
    {
        $filter = new static();

        // We currently only support types.
        if (property_exists($filterData, 'types')) {
            $types = (array) $filterData->types;
            $filter->setTypes($types);
        }

        return $filter;
    }

    public function setTypes(array $types): static
    {
        $this->types = $types;
        return $this;
    }

    public function getTypes(): array
    {
        return $this->types;
    }
}
