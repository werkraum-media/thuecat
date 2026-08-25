<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SysCategory;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\SysCategory\LongestChainStrategy;
use WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategies;

class ParentStrategiesTest extends TestCase
{
    private function strategies(): ParentStrategies
    {
        return new ParentStrategies(
            new LongestChainStrategy(),
            self::createStub(ImportLogger::class)
        );
    }

    #[Test]
    public function givesPlacesAndEventsDifferentStrategies(): void
    {
        $strategies = $this->strategies();

        self::assertNotSame(
            $strategies->forTable('tx_thuecat_tourist_attraction')->name(),
            $strategies->forTable('tx_events_domain_model_event')->name(),
            'What a parent should be depends on what the record is.'
        );
    }

    #[Test]
    public function reusesOneStrategyPerTable(): void
    {
        $strategies = $this->strategies();

        self::assertSame(
            $strategies->forTable('tx_thuecat_tourist_attraction'),
            $strategies->forTable('tx_thuecat_tourist_attraction')
        );
    }

    #[Test]
    public function fallsBackToTheDeepestBranchForATableWithNoRule(): void
    {
        self::assertSame(
            'longestChain',
            $this->strategies()->forTable('tx_thuecat_organisation')->name()
        );
    }
}
