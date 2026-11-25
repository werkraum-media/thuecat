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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\MeetingRoomEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\MeetingVenueEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TownEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailEntity;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

/**
 * A field missing from the TCA would be reconciled against a column that does not exist.
 */
final class ReconciledFieldsTest extends AbstractFunctionalTestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractEntity>}>
     */
    public static function reconciledKinds(): iterable
    {
        yield 'trail' => [TrailEntity::class];
        yield 'town' => [TownEntity::class];
        yield 'organisation' => [OrganisationEntity::class];
        yield 'event' => [EventEntity::class];
        yield 'meeting venue' => [MeetingVenueEntity::class];
        yield 'meeting room' => [MeetingRoomEntity::class];
    }

    /**
     * @param class-string<AbstractEntity> $entity
     */
    #[DataProvider('reconciledKinds')]
    #[Test]
    public function everyDeclaredFieldIsAColumnOfTheKindsTable(string $entity): void
    {
        $columns = $this->getTcaOfTable($entity::TABLE)['columns'] ?? null;
        $this->assertIsArray($columns);
        $columns = array_keys($columns);

        $this->assertNotSame([], $entity::RECONCILED_FIELDS);
        $this->assertSame([], array_values(array_diff(array_keys($entity::RECONCILED_FIELDS), $columns)));
    }
}
