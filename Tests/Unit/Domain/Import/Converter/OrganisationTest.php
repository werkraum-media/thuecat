<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Converter;

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

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class OrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function instanceCanBeCreated(): void
    {
        $subject = new Organisation();
        self::assertInstanceOf(Organisation::class, $subject);
    }

    /**
     * @test
     */
    public function isInstanceOfConverter(): void
    {
        $subject = new Organisation();
        self::assertInstanceOf(Converter::class, $subject);
    }

    /**
     * @test
     */
    public function canConvertTouristMarketingCompany(): void
    {
        $subject = new Organisation();
        self::assertTrue($subject->canConvert([
            'thuecat:TouristMarketingCompany',
            'schema:Thing',
            'ttgds:Organization',
        ]));
    }

    /**
     * @test
     */
    public function convertsJsonIdToGenericEntity(): void
    {
        $subject = new Organisation();
        $entity = $subject->convert([
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                '@value' => 'Description',
            ],
        ]);

        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_organisation', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
        ], $entity->getData());
    }
}
