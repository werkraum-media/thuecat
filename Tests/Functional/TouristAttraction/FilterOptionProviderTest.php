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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\CommaColumnOptionProvider;
use WerkraumMedia\ThueCat\Service\FilterField\TownFilterField;

class FilterOptionProviderTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilterOptions.php';
    }

    #[Test]
    public function commaColumnOffersEachValueItsRecordsCarry(): void
    {
        $options = $this->get(CommaColumnOptionProvider::class)->provide(
            new TownFilterField(),
            $this->scope()
        );

        // Erfurt from a single-value record, the rest from a record carrying
        // several. Deleted (4) and disabled (5) towns drop out, and town 6 has
        // only invisible records. "Stadt im fremden Ordner" sits outside the
        // list's storage but is still offered: filter values are site-bound.
        self::assertSame(
            ['Erfurt', 'Jena', 'Stadt im fremden Ordner', 'Weimar'],
            $this->titles($options)
        );
    }

    #[Test]
    public function commaColumnDoesNotOfferTheEmptyPlaceholder(): void
    {
        $options = $this->get(CommaColumnOptionProvider::class)->provide(
            new TownFilterField(),
            $this->scope()
        );

        // Guard the loop below: on an empty set it would assert nothing.
        self::assertNotSame([], $options->getOptions());
        foreach ($options as $option) {
            self::assertNotSame(0, $option->getUid());
        }
    }

    #[Test]
    public function offersAValueLivingOutsideTheListsStoragePages(): void
    {
        $options = $this->get(CommaColumnOptionProvider::class)->provide(
            new TownFilterField(),
            $this->scope()
        );

        // The list is bound to page 11; this town lives on page 12 and is
        // reached only through a record on page 11. Records are storage-bound,
        // the values they point at are not.
        self::assertContains('Stadt im fremden Ordner', $this->titles($options));
    }

    #[Test]
    public function dropsAValueLivingInAnotherSite(): void
    {
        $options = $this->get(CommaColumnOptionProvider::class)->provide(
            new TownFilterField(),
            $this->scope()
        );

        // Related by the same in-scope record as "Stadt im fremden Ordner", and
        // dropped only because it lives in another site.
        self::assertNotContains('Stadt der anderen Site', $this->titles($options));
    }

    #[Test]
    public function optionsAreOfferedUnderTheFieldsName(): void
    {
        $options = $this->get(CommaColumnOptionProvider::class)->provide(
            new TownFilterField(),
            $this->scope()
        );

        self::assertSame('towns', $options->getName());
    }

    private function scope(): FilterScope
    {
        return new FilterScope('tx_thuecat_tourist_attraction', [11], null, [], [1, 11, 12]);
    }

    /**
     * @return string[]
     */
    private function titles(FilterOptions $options): array
    {
        return array_map(
            static fn (FilterOption $option): string => $option->getTitle(),
            $options->getOptions()
        );
    }
}
