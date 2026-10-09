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
