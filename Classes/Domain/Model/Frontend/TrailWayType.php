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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * One segment of a trail's surface breakdown: a way type and how much of the
 * route it covers.
 */
class TrailWayType extends AbstractEntity
{
    protected string $title = '';

    protected string $length = '';

    protected string $lengthUnit = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLength(): string
    {
        return $this->length;
    }

    public function getLengthUnit(): string
    {
        return $this->lengthUnit;
    }
}
