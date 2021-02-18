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
use WerkraumMedia\ThueCat\Domain\Import\Converter\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Offers;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Organisation;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Town;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\TouristAttraction
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class TouristAttractionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $parser = $this->prophesize(Parser::class);
        $parserForOffers = $this->prophesize(Offers::class);
        $language = $this->prophesize(LanguageHandling::class);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $subject = new TouristAttraction(
            $parser->reveal(),
            $parserForOffers->reveal(),
            $language->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );

        self::assertInstanceOf(TouristAttraction::class, $subject);
    }

    /**
     * @test
     */
    public function canConvert(): void
    {
        $parser = $this->prophesize(Parser::class);
        $parserForOffers = $this->prophesize(Offers::class);
        $language = $this->prophesize(LanguageHandling::class);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $subject = new TouristAttraction(
            $parser->reveal(),
            $parserForOffers->reveal(),
            $language->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );

        self::assertTrue($subject->canConvert(['schema:TouristAttraction']));
    }

    /**
     * @test
     */
    public function convertsWithoutRelations(): void
    {
        $jsonLD = [
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
        ];

        $parser = $this->prophesize(Parser::class);
        $parser->getManagerId($jsonLD)->willReturn('https://example.com/resources/018132452787-xxxx');
        $parser->getContainedInPlaceIds($jsonLD)->willReturn([
            'https://example.com/resources/043064193523-jcyt',
            'https://example.com/resources/573211638937-gmqb',
        ]);
        $parser->getLanguages($jsonLD)->willReturn(['de']);
        $parser->getId($jsonLD)->willReturn('https://example.com/resources/018132452787-ngbe');
        $parser->getTitle($jsonLD, 'de')->willReturn('Title');
        $parser->getDescription($jsonLD, 'de')->willReturn('Description');
        $parser->getOpeningHours($jsonLD)->willReturn([]);
        $parser->getAddress($jsonLD)->willReturn([]);
        $parser->getMedia($jsonLD)->willReturn([]);

        $parserForOffers = $this->prophesize(Offers::class);
        $parserForOffers->get($jsonLD, 'de')->willReturn([]);

        $language = $this->prophesize(LanguageHandling::class);
        $language->isUnknown('de', 10)->willReturn(false);
        $language->getSystemUid('de', 10)->willReturn(0);

        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);

        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getStoragePid()->willReturn(10);

        $subject = new TouristAttraction(
            $parser->reveal(),
            $parserForOffers->reveal(),
            $language->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );

        $entities = $subject->convert($jsonLD, $configuration->reveal());

        self::assertInstanceOf(EntityCollection::class, $entities);
        self::assertCount(1, $entities->getEntities());

        $entity = $entities->getEntities()[0];
        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_tourist_attraction', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 0,
            'town' => 0,
            'opening_hours' => '[]',
            'address' => '[]',
            'media' => '[]',
            'offers' => '[]',
        ], $entity->getData());
    }

    /**
     * @test
     */
    public function convertsWithRelations(): void
    {
        $jsonLD = [
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
        ];

        $parser = $this->prophesize(Parser::class);
        $parser->getManagerId($jsonLD)->willReturn('https://example.com/resources/018132452787-xxxx');
        $parser->getContainedInPlaceIds($jsonLD)->willReturn([
            'https://example.com/resources/043064193523-jcyt',
            'https://example.com/resources/573211638937-gmqb',
        ]);
        $parser->getLanguages($jsonLD)->willReturn(['de']);
        $parser->getId($jsonLD)->willReturn('https://example.com/resources/018132452787-ngbe');
        $parser->getTitle($jsonLD, 'de')->willReturn('Title');
        $parser->getDescription($jsonLD, 'de')->willReturn('Description');
        $parser->getOpeningHours($jsonLD)->willReturn([]);
        $parser->getAddress($jsonLD)->willReturn([]);
        $parser->getMedia($jsonLD)->willReturn([]);

        $parserForOffers = $this->prophesize(Offers::class);
        $parserForOffers->get($jsonLD, 'de')->willReturn([]);

        $language = $this->prophesize(LanguageHandling::class);
        $language->isUnknown('de', 10)->willReturn(false);
        $language->getSystemUid('de', 10)->willReturn(0);

        $organisation = $this->prophesize(Organisation::class);
        $organisation->getUid()->willReturn(10);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $organisationRepository->findOneByRemoteId('https://example.com/resources/018132452787-xxxx')->willReturn($organisation->reveal());

        $town = $this->prophesize(Town::class);
        $town->getUid()->willReturn(20);
        $townRepository = $this->prophesize(TownRepository::class);
        $townRepository->findOneByRemoteIds([
            'https://example.com/resources/043064193523-jcyt',
            'https://example.com/resources/573211638937-gmqb',
        ])->willReturn($town->reveal());

        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getStoragePid()->willReturn(10);

        $subject = new TouristAttraction(
            $parser->reveal(),
            $parserForOffers->reveal(),
            $language->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal()
        );

        $entities = $subject->convert($jsonLD, $configuration->reveal());

        self::assertInstanceOf(EntityCollection::class, $entities);
        self::assertCount(1, $entities->getEntities());

        $entity = $entities->getEntities()[0];
        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_tourist_attraction', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
            'managed_by' => 10,
            'town' => 20,
            'opening_hours' => '[]',
            'address' => '[]',
            'media' => '[]',
            'offers' => '[]',
        ], $entity->getData());
    }
}
