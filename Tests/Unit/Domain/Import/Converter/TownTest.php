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
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Town;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Organisation;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\Town
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class TownTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function instanceCanBeCreated(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);

        $subject = new Town($organisationRepository->reveal());
        self::assertInstanceOf(Town::class, $subject);
    }

    /**
     * @test
     */
    public function isInstanceOfConverter(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);

        $subject = new Town($organisationRepository->reveal());
        self::assertInstanceOf(Converter::class, $subject);
    }

    /**
     * @test
     */
    public function canConvertTouristMarketingCompany(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);

        $subject = new Town($organisationRepository->reveal());
        self::assertTrue($subject->canConvert([
            'thuecat:Town',
        ]));
    }

    /**
     * @test
     */
    public function convertsJsonIdToGenericEntityWithoutOrganisation(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $organisationRepository->findOneByRemoteId('https://example.com/resources/018132452787-xxxx')->willReturn(null);

        $subject = new Town($organisationRepository->reveal());
        $entity = $subject->convert([
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'thuecat:managedBy' => [
                '@id' => 'https://example.com/resources/018132452787-xxxx',
            ],
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                '@value' => 'Description',
            ],
        ]);

        self::assertSame(95, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_town', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 0,
        ], $entity->getData());
    }

    /**
     * @test
     */
    public function convertsJsonIdToGenericEntityWithOrganisation(): void
    {
        $organisation = $this->prophesize(Organisation::class);
        $organisation->getUid()->willReturn(10);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $organisationRepository->findOneByRemoteId('https://example.com/resources/018132452787-xxxx')->willReturn($organisation->reveal());

        $subject = new Town($organisationRepository->reveal());
        $entity = $subject->convert([
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'thuecat:managedBy' => [
                '@id' => 'https://example.com/resources/018132452787-xxxx',
            ],
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                '@value' => 'Description',
            ],
        ]);

        self::assertSame(95, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_town', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 10,
        ], $entity->getData());
    }
}
