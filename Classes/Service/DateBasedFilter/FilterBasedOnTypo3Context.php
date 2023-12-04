<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Service\DateBasedFilter;

use DateTimeImmutable;
use TYPO3\CMS\Core\Context\Context;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

class FilterBasedOnTypo3Context implements DateBasedFilter
{
    public function __construct(
        private readonly Context $context
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
