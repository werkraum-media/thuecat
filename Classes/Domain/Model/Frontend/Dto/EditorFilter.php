<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

/**
 * Editor-configured filter: the demand values locked by settings plus the names
 * of the properties they cover, so the form can hide what the visitor must not
 * override.
 */
class EditorFilter
{
    /**
     * @param string[] $lockedProperties
     */
    public function __construct(
        protected readonly TouristAttractionDemand $demand,
        protected readonly array $lockedProperties,
    ) {
    }

    public function getDemand(): TouristAttractionDemand
    {
        return $this->demand;
    }

    /**
     * @return string[]
     */
    public function getLockedProperties(): array
    {
        return $this->lockedProperties;
    }

    public function isLocked(string $property): bool
    {
        return in_array($property, $this->lockedProperties, true);
    }

    /**
     * property => bool map for plain `{lockedMap.x}` access in Fluid (no
     * membership ViewHelper exists).
     *
     * @return array<string, bool>
     */
    public function getLockedMap(): array
    {
        return array_fill_keys($this->lockedProperties, true);
    }
}
