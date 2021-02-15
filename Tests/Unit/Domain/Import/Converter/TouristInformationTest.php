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
use WerkraumMedia\ThueCat\Domain\Import\Converter\TouristInformation;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Organisation;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Town;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\TouristInformation
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class TouristInformationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function instanceCanBeCreated(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $subject = new TouristInformation(
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );
        self::assertInstanceOf(TouristInformation::class, $subject);
    }

    /**
     * @test
     */
    public function isInstanceOfConverter(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $subject = new TouristInformation(
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );
        self::assertInstanceOf(Converter::class, $subject);
    }

    /**
     * @test
     */
    public function canConvertTouristMarketingCompany(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $subject = new TouristInformation(
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );
        self::assertTrue($subject->canConvert([
            'thuecat:TouristInformation',
        ]));
    }

    /**
     * @test
     */
    public function convertsJsonIdToGenericEntityWithoutRelations(): void
    {
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $organisationRepository->findOneByRemoteId('https://example.com/resources/018132452787-xxxx')
            ->willReturn(null);

        $townRepository = $this->prophesize(TownRepository::class);
        $townRepository->findOneByRemoteIds([
            'https://example.com/resources/043064193523-jcyt',
            'https://example.com/resources/573211638937-gmqb',
        ])->willReturn(null);

        $subject = new TouristInformation(
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );
        $entity = $subject->convert([
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'thuecat:managedBy' => [
                '@id' => 'https://example.com/resources/018132452787-xxxx',
            ],
            'schema:containedInPlace' => [
                [
                    '@id' => 'https://example.com/resources/043064193523-jcyt',
                ],
                [
                    '@id' => 'https://example.com/resources/573211638937-gmqb',
                ],
            ],
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                [
                    '@value' => 'Description',
                ],
            ],
        ]);

        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_tourist_information', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 0,
            'town' => 0,
        ], $entity->getData());
    }

    /**
     * @test
     */
    public function convertsJsonIdToGenericEntityWithRelations(): void
    {
        $organisation = $this->prophesize(Organisation::class);
        $organisation->getUid()->willReturn(10);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $organisationRepository->findOneByRemoteId('https://example.com/resources/018132452787-xxxx')
            ->willReturn($organisation->reveal());

        $town = $this->prophesize(Town::class);
        $town->getUid()->willReturn(20);
        $townRepository = $this->prophesize(TownRepository::class);
        $townRepository->findOneByRemoteIds([
            'https://example.com/resources/043064193523-jcyt',
            'https://example.com/resources/573211638937-gmqb',
        ])->willReturn($town->reveal());

        $subject = new TouristInformation(
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );
        $entity = $subject->convert([
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'thuecat:managedBy' => [
                '@id' => 'https://example.com/resources/018132452787-xxxx',
            ],
            'schema:containedInPlace' => [
                [
                    '@id' => 'https://example.com/resources/043064193523-jcyt',
                ],
                [
                    '@id' => 'https://example.com/resources/573211638937-gmqb',
                ],
            ],
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                [
                    '@value' => 'Description',
                ],
            ],
        ]);

        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_tourist_information', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 10,
            'town' => 20,
        ], $entity->getData());
    }
}
