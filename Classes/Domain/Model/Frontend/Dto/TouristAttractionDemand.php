<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto;

/**
 * carry search demand and filters for both list and search actions to use and stay in sync.
 * Potential sources:
 * - flexform configuration of list plugin
 * - user input via search form
 */
class TouristAttractionDemand
{
    protected string $searchword = '';

    /**
     * @var int[]
     */
    protected array $towns = [];

    /**
     * @var int[]
     */
    protected array $categories = [];

    /**
     * @var int[]
     */
    protected array $keywords = [];

    protected bool $petsAllowed = false;

    protected bool $isAccessibleForFree = false;

    protected bool $publicAccess = false;

    protected string $sortBy = '';

    /**
     * Properties the editor fixed; they apply without being part of URLs.
     *
     * @var list<string>
     */
    protected array $lockedProperties = [];

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

    /**
     * @return int[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param int[] $categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return int[]
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param int[] $keywords
     */
    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
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

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): void
    {
        $this->sortBy = $sortBy;
    }

    /**
     * Not a setter, so request mapping cannot reach it.
     *
     * @param list<string> $properties
     */
    public function lockProperties(array $properties): void
    {
        $this->lockedProperties = $properties;
    }

    /**
     * The search word as the readable `q` query argument, for links next to
     * getQueryParameters().
     *
     * @return array{q?: string}
     */
    public function getSearchQuery(): array
    {
        return $this->searchword === '' ? [] : ['q' => $this->searchword];
    }

    /**
     * Every active filter value, locked or not; empties dropped. The identity
     * of a result, independent of how URLs carry it.
     *
     * @return array<string, string|int|int[]>
     */
    public function getFilterState(): array
    {
        $state = [];
        /** @var array<string, string|int|int[]|bool> $properties */
        $properties = get_object_vars($this);
        // Editor-only and re-applied on every request; kept out of URLs and the cHash.
        unset($properties['sortBy'], $properties['lockedProperties']);
        foreach ($properties as $name => $value) {
            if ($value === '' || $value === [] || $value === false) {
                continue;
            }
            $state[$name] = $value === true ? 1 : $value;
        }

        return $state;
    }

    /**
     * Flat shape for GET URLs (f:link.action / POST redirect): locked
     * properties dropped, lists comma-joined as the route aspects map them.
     * The searchword travels as `q`, see getSearchQuery().
     *
     * @return array<string, string|int>
     */
    public function getQueryParameters(): array
    {
        $parameters = [];
        foreach ($this->getFilterState() as $name => $value) {
            if ($name === 'searchword' || in_array($name, $this->lockedProperties, true)) {
                continue;
            }
            $parameters[$name] = is_array($value) ? implode(',', $value) : $value;
        }

        return $parameters;
    }
}
