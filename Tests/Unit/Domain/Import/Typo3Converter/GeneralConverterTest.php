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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\ForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Town;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Import\ResolveForeignReference;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\GeneralConverter;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\NameExtractor;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\OrganisationRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ParkingFacilityRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\TownRepository;

class GeneralConverterTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $resolveForeignReference = $this->createStub(ResolveForeignReference::class);
        $importer = $this->createStub(Importer::class);
        $languageHandling = $this->createStub(LanguageHandling::class);
        $organisationRepository = $this->createStub(OrganisationRepository::class);
        $townRepository = $this->createStub(TownRepository::class);
        $parkingFacilityRepository = $this->createStub(ParkingFacilityRepository::class);
        $nameExtractor = $this->createStub(NameExtractor::class);
        $logManager = $this->createStub(LogManager::class);
        $logManager->method('getLogger')->willReturn($this->createStub(Logger::class));

        $subject = new GeneralConverter(
            $resolveForeignReference,
            $importer,
            $languageHandling,
            $organisationRepository,
            $townRepository,
            $parkingFacilityRepository,
            $nameExtractor,
            $logManager
        );

        self::assertInstanceOf(
            GeneralConverter::class,
            $subject
        );
    }

    #[Test]
    public function skipsWithoutManager(): void
    {
        $resolveForeignReference = $this->createStub(ResolveForeignReference::class);
        $importer = $this->createStub(Importer::class);
        $importer->method('importConfiguration')->willReturn(new ImportLog());
        $languageHandling = $this->createStub(LanguageHandling::class);
        $languageHandling->method('getLanguageUidForString')->willReturn(0);
        $organisationRepository = $this->createStub(OrganisationRepository::class);
        $townRepository = $this->createStub(TownRepository::class);
        $parkingFacilityRepository = $this->createStub(ParkingFacilityRepository::class);
        $nameExtractor = $this->createStub(NameExtractor::class);
        $logManager = $this->createStub(LogManager::class);
        $logManager->method('getLogger')->willReturn($this->createStub(Logger::class));

        $subject = new GeneralConverter(
            $resolveForeignReference,
            $importer,
            $languageHandling,
            $organisationRepository,
            $townRepository,
            $parkingFacilityRepository,
            $nameExtractor,
            $logManager
        );

        $contentResponsible = new ForeignReference();
        $contentResponsible->setId('https://example.com/content-responsible');
        $entity = new Town();
        $entity->setName('Test Name');
        $entity->setContentResponsible($contentResponsible);

        $configuration = new ImportConfiguration();
        $configuration->_setProperty('storagePid', 10);

        self::assertNull(
            $subject->convert($entity, $configuration, 'de')
        );
    }
}
