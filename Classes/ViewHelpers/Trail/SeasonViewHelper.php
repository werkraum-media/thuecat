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

namespace WerkraumMedia\ThueCat\ViewHelpers\Trail;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use WerkraumMedia\ThueCat\Domain\Model\TrailSeason;

/**
 * Resolves a trail's season bitmask into the keys of the members it carries, in
 * the order the bits are declared. Keys, not labels: the rendering template owns
 * its wording and translates them against its own language file.
 */
class SeasonViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('season', 'int', 'The trail season bitmask', true);
    }

    /**
     * @return string[]
     */
    public function render(): array
    {
        $season = $this->arguments['season'];
        if (!is_int($season) || $season === 0) {
            return [];
        }

        return array_map(
            static fn (TrailSeason $member): string => $member->labelKey(),
            TrailSeason::fromMask($season)
        );
    }
}
