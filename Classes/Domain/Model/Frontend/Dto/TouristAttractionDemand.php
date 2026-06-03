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
}
