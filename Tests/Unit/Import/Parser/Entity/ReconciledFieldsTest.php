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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\MeetingRoomEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\MeetingVenueEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TownEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailEntity;
use WerkraumMedia\ThueCat\Import\Reconciliation\FieldMechanism;

final class ReconciledFieldsTest extends TestCase
{
    /**
     * Properties toArray() never writes as a column.
     */
    private const array NO_COLUMNS = ['remote_id', 'transients', 'priority', 'translations', 'children'];

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
    public function kindDeclaresItsOwnFields(string $entity): void
    {
        $declaration = new ReflectionClassConstant($entity, 'RECONCILED_FIELDS');

        $this->assertSame($entity, $declaration->getDeclaringClass()->getName());
    }

    /**
     * @param class-string<AbstractEntity> $entity
     */
    #[DataProvider('reconciledKinds')]
    #[Test]
    public function everyColumnTheKindWritesIsDeclared(string $entity): void
    {
        $columns = array_values(array_filter(
            array_map(
                static fn (ReflectionProperty $property): string => $property->getName(),
                new ReflectionClass($entity)->getProperties()
            ),
            static fn (string $name): bool => !in_array($name, self::NO_COLUMNS, true) && !str_starts_with($name, '_')
        ));

        $this->assertSame([], array_values(array_diff($columns, array_keys($entity::RECONCILED_FIELDS))));
    }

    /**
     * @param class-string<AbstractEntity> $entity
     */
    #[DataProvider('reconciledKinds')]
    #[Test]
    public function mediaFieldsAreRemovedByDeletingTheirReferences(string $entity): void
    {
        $media = array_values(array_unique($entity::MEDIA_FIELDS));

        $this->assertEquals(
            array_fill_keys($media, FieldMechanism::ExplicitDelete),
            array_intersect_key($entity::RECONCILED_FIELDS, array_flip($media))
        );
    }
}
