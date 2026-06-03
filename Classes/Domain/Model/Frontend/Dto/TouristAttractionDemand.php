<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

class TouristAttractionDemand
{
    protected string $searchword = '';

    /**
     * @var int[]
     */
    protected array $towns = [];

    protected bool $petsAllowed = false;

    protected bool $isAccessibleForFree = false;

    protected bool $publicAccess = false;

    public function getSearchword(): string
    {
        return $this->searchword;
    }

    public function setSearchword(string $searchword): void
    {
        $this->searchword = $searchword;
    }

    /**
     * @return int[]
     */
    public function getTowns(): array
    {
        return $this->towns;
    }

    /**
     * @param int[] $towns
     */
    public function setTowns(array $towns): void
    {
        $this->towns = $towns;
    }

    public function getPetsAllowed(): bool
    {
        return $this->petsAllowed;
    }

    public function setPetsAllowed(bool $petsAllowed): void
    {
        $this->petsAllowed = $petsAllowed;
    }

    public function getIsAccessibleForFree(): bool
    {
        return $this->isAccessibleForFree;
    }

    public function setIsAccessibleForFree(bool $isAccessibleForFree): void
    {
        $this->isAccessibleForFree = $isAccessibleForFree;
    }

    public function getPublicAccess(): bool
    {
        return $this->publicAccess;
    }

    public function setPublicAccess(bool $publicAccess): void
    {
        $this->publicAccess = $publicAccess;
    }

    /**
     * Flat shape for GET URLs (f:link.action / POST redirect); empties dropped.
     * Iterates properties so new filters are included without touching this.
     *
     * @return array<string, string|int|int[]>
     */
    public function getQueryParameters(): array
    {
        $parameters = [];
        /** @var array<string, string|int|int[]|bool> $properties */
        $properties = get_object_vars($this);
        foreach ($properties as $name => $value) {
            if ($value === '' || $value === [] || $value === false) {
                continue;
            }
            $parameters[$name] = $value === true ? 1 : $value;
        }

        return $parameters;
    }
}
