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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Typo3Converter;

use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Town;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\GeneralConverter;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ParkingFacilityRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\GeneralConverter
 */
class GeneralConverterTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $resolveForeignReference = $this->prophesize(ResolveForeignReference::class);
        $importer = $this->prophesize(Importer::class);
        $languageHandling = $this->prophesize(LanguageHandling::class);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);
        $parkingFacilityRepository = $this->prophesize(ParkingFacilityRepository::class);

        $subject = new GeneralConverter(
            $resolveForeignReference->reveal(),
            $importer->reveal(),
            $languageHandling->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal(),
            $parkingFacilityRepository->reveal()
        );

        self::assertInstanceOf(
            GeneralConverter::class,
            $subject
        );
    }

    /**
     * @test
     */
    public function skipsWithoutManager(): void
    {
        $resolveForeignReference = $this->prophesize(ResolveForeignReference::class);
        $importer = $this->prophesize(Importer::class);
        $importer->importConfiguration(Argument::any())->willReturn(new ImportLog());
        $languageHandling = $this->prophesize(LanguageHandling::class);
        $languageHandling->getLanguageUidForString(10, 'de')->willReturn(0);
        $organisationRepository = $this->prophesize(OrganisationRepository::class);
        $townRepository = $this->prophesize(TownRepository::class);
        $parkingFacilityRepository = $this->prophesize(ParkingFacilityRepository::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $subject = new GeneralConverter(
            $resolveForeignReference->reveal(),
            $importer->reveal(),
            $languageHandling->reveal(),
            $organisationRepository->reveal(),
            $townRepository->reveal(),
            $parkingFacilityRepository->reveal()
        );
        $subject->setLogger($logger->reveal());

        $contentResponsible = new ForeignReference();
        $contentResponsible->setId('https://example.com/content-responsible');
        $entity = new Town();
        $entity->setName('Test Name');
        $entity->setContentResponsible($contentResponsible);

        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getStoragePid()->willReturn(10);

        self::assertNull(
            $subject->convert($entity, $configuration->reveal(), 'de')
        );
    }
}
