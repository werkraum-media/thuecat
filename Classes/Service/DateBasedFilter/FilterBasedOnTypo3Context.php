<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Service\DateBasedFilter;

use DateTimeImmutable;
use TYPO3\CMS\Core\Context\Context;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

class FilterBasedOnTypo3Context implements DateBasedFilter
{
    public function __construct(
        protected readonly Context $context
    ) {
    }

    /**
     * Filters out all objects where the date is prior the reference date.
     *
     * The reference date is now.
     */
    public function filterOutPreviousDates(
        array $listToFilter,
        callable $provideDate
    ): array {
        $referenceDate = $this->context->getPropertyFromAspect('date', 'full', new DateTimeImmutable());

        return array_filter($listToFilter, function ($elementToFilter) use ($referenceDate, $provideDate) {
            $objectDate = $provideDate($elementToFilter);
            return $objectDate === null || $objectDate >= $referenceDate;
        });
    }
}
