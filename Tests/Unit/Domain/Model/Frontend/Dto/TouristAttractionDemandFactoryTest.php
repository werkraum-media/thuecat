<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

        $this->assertSame([], $filter->getLockedProperties());
    }

    #[Test]
    public function townsSettingParsesCsvAndLocksTowns(): void
    {
        $filter = $this->subject->fromSettings(['towns' => '1, 3']);

        $this->assertSame([1, 3], $filter->getDemand()->getTowns());
        $this->assertTrue($filter->isLocked('towns'));
    }

    #[Test]
    public function nonStringTownsSettingIsIgnored(): void
    {
        // Flexform may hand an array; only a CSV string is a valid towns lock.
        $filter = $this->subject->fromSettings(['towns' => [1, 3]]);

        $this->assertSame([], $filter->getDemand()->getTowns());
        $this->assertFalse($filter->isLocked('towns'));
    }

    #[Test]
    public function categoriesSettingParsesCsvAndLocksCategories(): void
    {
        $filter = $this->subject->fromSettings(['categories' => '10, 12']);

        $this->assertSame([10, 12], $filter->getDemand()->getCategories());
        $this->assertTrue($filter->isLocked('categories'));
    }

    #[Test]
    public function nonStringCategoriesSettingIsIgnored(): void
    {
        $filter = $this->subject->fromSettings(['categories' => [10, 12]]);

        $this->assertSame([], $filter->getDemand()->getCategories());
        $this->assertFalse($filter->isLocked('categories'));
    }

    #[Test]
    public function applyForcesLockedCategoryOverVisitorInput(): void
    {
        // Visitor asks for 5,6; editor locked 10 -> visitor cannot widen.
        $filter = $this->subject->fromSettings(['categories' => '10']);
        $demand = new TouristAttractionDemand();
        $demand->setCategories([5, 6]);

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame([10], $demand->getCategories());
    }

    #[Test]
    public function keywordsSettingParsesCsvAndLocksCategories(): void
    {
        $filter = $this->subject->fromSettings(['keywords' => '22,75']);

        $this->assertSame([22, 75], $filter->getDemand()->getKeywords());
        $this->assertTrue($filter->isLocked('keywords'));
    }

    #[Test]
    public function nonStringKeywordsSettingIsIgnored(): void
    {
        $filter = $this->subject->fromSettings(['keywords' => [22, 75]]);

        $this->assertSame([], $filter->getDemand()->getKeywords());
        $this->assertFalse($filter->isLocked('keywords'));
    }

    #[Test]
    public function applyForcesLockedKeywordOverVisitorInput(): void
    {
        // Visitor asks for 1,2; editor locked 8 -> visitor cannot widen.
        $filter = $this->subject->fromSettings(['categories' => '8']);
        $demand = new TouristAttractionDemand();
        $demand->setCategories([1, 2]);

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame([8], $demand->getCategories());
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
        $this->assertTrue($demand->getPetsAllowed());
        $this->assertTrue($demand->getIsAccessibleForFree());
        $this->assertTrue($demand->getPublicAccess());
        $this->assertSame(['petsAllowed', 'isAccessibleForFree', 'publicAccess'], $filter->getLockedProperties());
    }

    #[Test]
    public function applyForcesLockedValueOverVisitorInput(): void
    {
        // Visitor asks for towns 5,6; editor locked town 1 -> visitor cannot widen.
        $filter = $this->subject->fromSettings(['towns' => '1']);
        $demand = new TouristAttractionDemand();
        $demand->setTowns([5, 6]);

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame([1], $demand->getTowns());
    }

    #[Test]
    public function sortBySettingIsCarriedWithoutLockingIt(): void
    {
        // Locked properties render as hidden form fields; the sort order must not.
        $filter = $this->subject->fromSettings(['sortBy' => 'sorting']);

        $this->assertSame('sorting', $filter->getDemand()->getSortBy());
        $this->assertSame([], $filter->getLockedProperties());
    }

    #[Test]
    public function applyForcesTheEditorsSortByOverVisitorInput(): void
    {
        $filter = $this->subject->fromSettings(['sortBy' => 'sorting']);
        $demand = new TouristAttractionDemand();
        $demand->setSortBy('title');

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame('sorting', $demand->getSortBy());
    }

    #[Test]
    public function applyForcesAnEmptySortByOverVisitorInput(): void
    {
        $filter = $this->subject->fromSettings([]);
        $demand = new TouristAttractionDemand();
        $demand->setSortBy('sorting');

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame('', $demand->getSortBy());
    }

    #[Test]
    public function applyLeavesUnlockedPropertyUntouched(): void
    {
        // Only towns locked; the visitor's searchword must survive.
        $filter = $this->subject->fromSettings(['towns' => '1']);
        $demand = new TouristAttractionDemand();
        $demand->setSearchword('Dom');

        $this->subject->applyEditorFilter($demand, $filter);

        $this->assertSame('Dom', $demand->getSearchword());
    }
}
