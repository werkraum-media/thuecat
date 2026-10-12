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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\DetailRecordKind;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKind;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKinds;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\TouristAttractionKind;
use WerkraumMedia\ThueCat\Domain\DetailRecordKind\TrailKind;

final class DetailRecordKindsTest extends TestCase
{
    #[Test]
    public function returnsBuiltInKinds(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);

        self::assertEquals(
            [
                new DetailRecordKind(
                    'attraction',
                    'tx_thuecat_tourist_attraction',
                    'ThueCat',
                    'TouristAttractionShow',
                    'attraction',
                    'thuecat.pois.pid_show',
                ),
                new DetailRecordKind(
                    'trail',
                    'tx_thuecat_trail',
                    'ThueCat',
                    'TrailShow',
                    'trail',
                    'thuecat.trails.pid_show',
                ),
            ],
            $subject->all()
        );
    }

    #[Test]
    public function resolvesKindByIdentifier(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);

        self::assertSame('tx_thuecat_trail', $subject->get('trail')->table);
    }

    #[Test]
    public function resolvesKindByTable(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);

        self::assertSame('attraction', $subject->forTable('tx_thuecat_tourist_attraction')?->identifier);
    }

    #[Test]
    public function returnsNoKindForTableWithoutDetailPage(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);

        self::assertNull($subject->forTable('tx_thuecat_town'));
    }

    #[Test]
    public function throwsForUnknownIdentifier(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1791786566);

        $subject->get('meeting_place');
    }

    #[Test]
    public function resolvesShowPageFromSiteSettings(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);
        $site = new Site('example', 1, ['settings' => ['thuecat' => ['pois' => ['pid_show' => 71]]]]);

        self::assertSame(71, $subject->showPageUid($subject->get('attraction'), $site));
    }

    #[Test]
    public function resolvesShowPageToZeroWhenSiteNamesNone(): void
    {
        $subject = new DetailRecordKinds([new TouristAttractionKind(), new TrailKind()]);
        $site = new Site('example', 1, ['settings' => ['thuecat' => ['pois' => ['pid_show' => 71]]]]);

        self::assertSame(0, $subject->showPageUid($subject->get('trail'), $site));
    }
}
