<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
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
