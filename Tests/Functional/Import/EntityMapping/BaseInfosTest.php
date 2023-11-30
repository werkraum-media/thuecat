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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\EntityMapping;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Base;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;

class BaseInfosTest extends TestCase
{
    #[Test]
    public function instanceOfBaseIsReturnedIfRequestes(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
    }

    #[Test]
    public function returnsDefaultValuesIfNotProvidedForMapping(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
        self::assertSame('', $result->getId());
        self::assertSame('', $result->getName());
        self::assertSame('', $result->getDescription());
        self::assertSame([], $result->getUrls());
        self::assertNull($result->getPhoto());
        self::assertSame([], $result->getImages());
        self::assertNull($result->getManagedBy());
    }

    #[Test]
    public function mapsIncomingDataToProperties(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            '@id' => 'https://thuecat.org/resources/835224016581-dara',
            'schema:name' => 'The name of the Thing',
            'schema:description' => 'This is some long description describing this Thing.',
            'schema:url' => 'https://example.com/the-thing',
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
        self::assertSame('https://thuecat.org/resources/835224016581-dara', $result->getId());
        self::assertSame('The name of the Thing', $result->getName());
        self::assertSame('This is some long description describing this Thing.', $result->getDescription());
        self::assertSame(['https://example.com/the-thing'], $result->getUrls());
    }

    #[Test]
    public function mapsIncomingPhoto(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:photo' => [
                '@id' => 'https://thuecat.org/resources/835224016581-dara',
            ],
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
        self::assertInstanceOf(ForeignReference::class, $result->getPhoto());
        self::assertSame('https://thuecat.org/resources/835224016581-dara', $result->getPhoto()->getId());
    }

    #[Test]
    public function mapsIncomingImages(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'schema:image' => [
                [
                    '@id' => 'https://thuecat.org/resources/835224016581-1st',
                ],
                [
                    '@id' => 'https://thuecat.org/resources/835224016581-2nd',
                ],
            ],
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
        self::assertIsArray($result->getImages());
        foreach ($result->getImages() as $image) {
            self::assertInstanceOf(ForeignReference::class, $image);
        }
        self::assertSame('https://thuecat.org/resources/835224016581-1st', $result->getImages()[0]->getId());
        self::assertSame('https://thuecat.org/resources/835224016581-2nd', $result->getImages()[1]->getId());
    }

    #[Test]
    public function mapsIncomingManagedBy(): void
    {
        $subject = new EntityMapper();

        $result = $subject->mapDataToEntity([
            'thuecat:contentResponsible' => [
                '@id' => 'https://thuecat.org/resources/835224016581-1st',
            ],
        ], Base::class, [
            JsonDecode::ACTIVE_LANGUAGE => 'de',
        ]);

        self::assertInstanceOf(Base::class, $result);
        self::assertInstanceOf(ForeignReference::class, $result->getManagedBy());
        self::assertSame('https://thuecat.org/resources/835224016581-1st', $result->getManagedBy()->getId());
    }
}
