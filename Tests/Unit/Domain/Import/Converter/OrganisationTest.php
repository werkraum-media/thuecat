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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation;
use WerkraumMedia\ThueCat\Domain\Import\Importer\LanguageHandling;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Converter\Organisation
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection
 * @uses WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class OrganisationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function instanceCanBeCreated(): void
    {
        $parser = $this->prophesize(Parser::class);
        $language = $this->prophesize(LanguageHandling::class);

        $subject = new Organisation(
            $parser->reveal(),
            $language->reveal()
        );

        self::assertInstanceOf(Organisation::class, $subject);
    }

    /**
     * @test
     */
    public function isInstanceOfConverter(): void
    {
        $parser = $this->prophesize(Parser::class);
        $language = $this->prophesize(LanguageHandling::class);

        $subject = new Organisation(
            $parser->reveal(),
            $language->reveal()
        );

        self::assertInstanceOf(Converter::class, $subject);
    }

    /**
     * @test
     */
    public function canConvertTouristMarketingCompany(): void
    {
        $parser = $this->prophesize(Parser::class);
        $language = $this->prophesize(LanguageHandling::class);

        $subject = new Organisation(
            $parser->reveal(),
            $language->reveal()
        );

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
        $jsonLD = [
            '@id' => 'https://example.com/resources/018132452787-ngbe',
            'schema:name' => [
                '@value' => 'Title',
            ],
            'schema:description' => [
                '@value' => 'Description',
            ],
        ];

        $siteLanguage = $this->prophesize(SiteLanguage::class);

        $language = $this->prophesize(LanguageHandling::class);
        $language->getDefaultLanguage(10)->willReturn($siteLanguage->reveal());

        $parser = $this->prophesize(Parser::class);
        $parser->getId($jsonLD)->willReturn('https://example.com/resources/018132452787-ngbe');
        $parser->getTitle($jsonLD, $siteLanguage->reveal())->willReturn('Title');
        $parser->getDescription($jsonLD, $siteLanguage->reveal())->willReturn('Description');

        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getStoragePid()->willReturn(10);

        $subject = new Organisation(
            $parser->reveal(),
            $language->reveal()
        );

        $entities = $subject->convert($jsonLD, $configuration->reveal());

        self::assertInstanceOf(EntityCollection::class, $entities);
        self::assertCount(1, $entities->getEntities());

        $entity = $entities->getEntities()[0];
        self::assertSame(10, $entity->getTypo3StoragePid());
        self::assertSame('tx_thuecat_organisation', $entity->getTypo3DatabaseTableName());
        self::assertSame('https://example.com/resources/018132452787-ngbe', $entity->getRemoteId());
        self::assertSame([
            'title' => 'Title',
            'description' => 'Description',
        ], $entity->getData());
    }
}
