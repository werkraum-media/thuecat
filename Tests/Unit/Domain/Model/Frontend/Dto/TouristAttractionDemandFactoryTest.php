<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Frontend\Dto;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;

/**
 * The editor filtering plugin's contract: settings become a locked demand, and
 * applying that demand to a visitor's input may only narrow, never widen, the set.
 * Each test guards a single rule so a regression points at the exact violation.
 */
final class TouristAttractionDemandFactoryTest extends TestCase
{
    private TouristAttractionDemandFactory $subject;

    protected function setUp(): void
    {
        $this->subject = new TouristAttractionDemandFactory();
    }

    #[Test]
    public function emptySettingsLockNothing(): void
    {
        $filter = $this->subject->fromSettings([]);

        self::assertSame([], $filter->getLockedProperties());
    }

    #[Test]
    public function townsSettingParsesCsvAndLocksTowns(): void
    {
        $filter = $this->subject->fromSettings(['towns' => '1, 3']);

        self::assertSame([1, 3], $filter->getDemand()->getTowns());
        self::assertTrue($filter->isLocked('towns'));
    }

    #[Test]
    public function nonStringTownsSettingIsIgnored(): void
    {
        // Flexform may hand an array; only a CSV string is a valid towns lock.
        $filter = $this->subject->fromSettings(['towns' => [1, 3]]);

        self::assertSame([], $filter->getDemand()->getTowns());
        self::assertFalse($filter->isLocked('towns'));
    }

    #[Test]
    public function booleanSettingsSetAndLockTheirProperty(): void
    {
        $filter = $this->subject->fromSettings([
            'petsAllowed' => '1',
            'isAccessibleForFree' => '1',
            'publicAccess' => '1',
        ]);

        $demand = $filter->getDemand();
        self::assertTrue($demand->getPetsAllowed());
        self::assertTrue($demand->getIsAccessibleForFree());
        self::assertTrue($demand->getPublicAccess());
        self::assertSame(
            ['petsAllowed', 'isAccessibleForFree', 'publicAccess'],
            $filter->getLockedProperties()
        );
    }

    #[Test]
    public function applyForcesLockedValueOverVisitorInput(): void
    {
        // Visitor asks for towns 5,6; editor locked town 1 -> visitor cannot widen.
        $filter = $this->subject->fromSettings(['towns' => '1']);
        $demand = new TouristAttractionDemand();
        $demand->setTowns([5, 6]);

        $this->subject->applyEditorFilter($demand, $filter);

        self::assertSame([1], $demand->getTowns());
    }

    #[Test]
    public function applyLeavesUnlockedPropertyUntouched(): void
    {
        // Only towns locked; the visitor's searchword must survive.
        $filter = $this->subject->fromSettings(['towns' => '1']);
        $demand = new TouristAttractionDemand();
        $demand->setSearchword('Dom');

        $this->subject->applyEditorFilter($demand, $filter);

        self::assertSame('Dom', $demand->getSearchword());
    }
}
