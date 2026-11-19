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

namespace WerkraumMedia\ThueCat\Removal;

use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Registry;

/**
 * Brings the reference index up to date before a job judges usage by it.
 *
 * Public, so functional tests can put a fixed clock in place.
 */
#[Autoconfigure(public: true)]
class ReferenceIndexUpdate
{
    protected const RECENT_SECONDS = 86400;

    public function __construct(
        protected readonly ReferenceIndex $referenceIndex,
        protected readonly Registry $registry,
        protected readonly ClockInterface $clock,
    ) {
    }

    /**
     * Core records every full update, whoever started it.
     */
    public function updateUnlessRecent(): void
    {
        /** @var int $lastUpdate */
        $lastUpdate = $this->registry->get('core', 'sys_refindex_lastUpdate', 0);
        if ($this->clock->now()->getTimestamp() - $lastUpdate < static::RECENT_SECONDS) {
            return;
        }

        $this->referenceIndex->updateIndex(false);
    }
}
