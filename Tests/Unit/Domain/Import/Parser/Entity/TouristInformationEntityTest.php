<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristInformationEntity;

final class TouristInformationEntityTest extends AbstractImportTestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $subject = new TouristInformationEntity();

        self::assertSame('tx_thuecat_tourist_information', $subject->table);
    }

    #[Test]
    public function handlesTouristInformationType(): void
    {
        $subject = new TouristInformationEntity();

        self::assertSame(['thuecat:TouristInformation'], $subject->handlesTypes());
    }

    #[Test]
    public function returnsTitleAndDescriptionForDefaultLanguage(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'de');

        $row = $subject->toArray();

        self::assertSame('Erfurt Tourist Information', $row['title']);
        self::assertStringStartsWith('Direkt an der Krämerbrücke', (string)$row['description']);
    }

    #[Test]
    public function titleAndDescriptionAreOmittedForUnmatchedLanguage(): void
    {
        // Fixture only carries German entries; picking a language that is not
        // present must yield '' rather than silently falling back to German.
        // toArray() then drops the empty strings, so the keys disappear entirely.
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'en');

        $row = $subject->toArray();

        self::assertArrayNotHasKey('title', $row);
        self::assertArrayNotHasKey('description', $row);
    }

    #[Test]
    public function rowContainsRemoteId(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'de');

        $row = $subject->toArray();

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $row['remote_id']);
    }

    #[Test]
    public function rowOmitsRelationFieldsForResolverToFill(): void
    {
        // The resolver decides the target row for each referenced @id after a
        // type lookup, so the parser must not pre-fill town or managed_by.
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'de');

        $row = $subject->toArray();

        self::assertArrayNotHasKey('town', $row);
        self::assertArrayNotHasKey('managed_by', $row);
    }

    #[Test]
    public function capturesContainedInPlaceRefsAsTransient(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'de');

        $transients = $subject->getTransients();

        self::assertArrayHasKey('containedInPlace', $transients);
        self::assertSame([
            'https://thuecat.org/resources/043064193523-jcyt',
            'https://thuecat.org/resources/573211638937-gmqb',
            'https://thuecat.org/resources/e_108867196-oatour',
            'https://thuecat.org/resources/e_1492818-oatour',
            'https://thuecat.org/resources/e_16571065-oatour',
            'https://thuecat.org/resources/e_16659193-oatour',
            'https://thuecat.org/resources/e_18179059-oatour',
            'https://thuecat.org/resources/e_18429754-oatour',
            'https://thuecat.org/resources/e_18429974-oatour',
            'https://thuecat.org/resources/e_18550292-oatour',
            'https://thuecat.org/resources/e_21827958-oatour',
            'https://thuecat.org/resources/e_39285647-oatour',
            'https://thuecat.org/resources/e_52469786-oatour',
            'https://thuecat.org/resources/356133173991-cryw',
        ], $transients['containedInPlace']);
    }

    #[Test]
    public function capturesManagedByRefAsTransient(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json', 'thuecat:TouristInformation');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity();
        $subject->parse($node, 'de');

        $transients = $subject->getTransients();

        self::assertArrayHasKey('managedBy', $transients);
        self::assertSame(
            ['https://thuecat.org/resources/018132452787-ngbe'],
            $transients['managedBy']
        );
    }

    #[Test]
    public function transientsAreEmptyWhenNodeLacksRelations(): void
    {
        $subject = new TouristInformationEntity();
        $subject->parse([
            '@id' => 'https://thuecat.org/resources/no-relations',
            '@type' => ['thuecat:TouristInformation'],
        ], 'de');

        self::assertSame([], $subject->getTransients());
    }
}
