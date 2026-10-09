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

    /**
     * Flat shape for GET URLs (f:link.action / POST redirect); empties dropped.
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
