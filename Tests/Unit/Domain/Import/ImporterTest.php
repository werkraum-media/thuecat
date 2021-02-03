<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import;

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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Registry as ConverterRegistry;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Importer\SaveData;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry as UrlProviderRegistry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportLogRepository;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Importer
 * @uses WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog
 */
class ImporterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        self::assertInstanceOf(Importer::class, $subject);
    }

    /**
     * @test
     */
    public function importsNothingIfUrlProviderCouldNotBeResolved(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);
        $configuration = $this->prophesize(ImportConfiguration::class);

        $urls->getProviderForConfiguration($configuration->reveal())->willReturn(null);
        $fetchData->jsonLDFromUrl()->shouldNotBeCalled();
        $saveData->import()->shouldNotBeCalled();

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        $result = $subject->importConfiguration($configuration->reveal());

        self::assertInstanceOf(ImportLog::class, $result);
        self::assertCount(0, $result->getEntries());
    }

    /**
     * @test
     */
    public function importsNothingIfNoUrlProviderIsGiven(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $urlProvider = $this->prophesize(UrlProvider::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);
        $configuration = $this->prophesize(ImportConfiguration::class);

        $urls->getProviderForConfiguration($configuration->reveal())->willReturn($urlProvider->reveal());
        $urlProvider->getUrls()->willReturn([]);
        $fetchData->jsonLDFromUrl()->shouldNotBeCalled();
        $saveData->import()->shouldNotBeCalled();

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        $subject->importConfiguration($configuration->reveal());
    }

    /**
     * @test
     */
    public function importsAllUrlsFromAllUrlProvider(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $urlProvider = $this->prophesize(UrlProvider::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $concreteConverter = $this->prophesize(Converter::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);
        $configuration = $this->prophesize(ImportConfiguration::class);

        $entities1 = $this->prophesize(EntityCollection::class);
        $entities2 = $this->prophesize(EntityCollection::class);

        $urls->getProviderForConfiguration($configuration->reveal())->willReturn($urlProvider->reveal());
        $urlProvider->getUrls()->willReturn([
            'https://example.com/resources/34343-ex',
            'https://example.com/resources/34344-es',
        ]);

        $fetchData->jsonLDFromUrl('https://example.com/resources/34343-ex')->willReturn(['@graph' => [
            [
                '@id' => 'https://example.com/resources/34343-ex',
                '@type' => [
                    'schema:Organization',
                    'thuecat:TouristMarketingCompany',
                ],
            ],
        ]]);
        $fetchData->jsonLDFromUrl('https://example.com/resources/34344-es')->willReturn(['@graph' => [
            [
                '@id' => 'https://example.com/resources/34344-es',
                '@type' => [
                    'schema:Organization',
                    'thuecat:TouristMarketingCompany',
                ],
            ],
        ]]);

        $converter->getConverterBasedOnType([
            'schema:Organization',
            'thuecat:TouristMarketingCompany',
        ])->willReturn($concreteConverter->reveal());

        $concreteConverter->convert(Argument::that(function (array $jsonEntity) {
            return $jsonEntity['@id'] === 'https://example.com/resources/34343-ex';
        }))->willReturn($entities1->reveal());
        $concreteConverter->convert(Argument::that(function (array $jsonEntity) {
            return $jsonEntity['@id'] === 'https://example.com/resources/34344-es';
        }))->willReturn($entities2->reveal());

        $saveData->import($entities1->reveal(), Argument::type(ImportLog::class))->shouldBeCalled();
        $saveData->import($entities2->reveal(), Argument::type(ImportLog::class))->shouldBeCalled();

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        $subject->importConfiguration($configuration->reveal());
    }

    /**
     * @test
     */
    public function handlesMissingConverter(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $urlProvider = $this->prophesize(UrlProvider::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $concreteConverter = $this->prophesize(Converter::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);
        $configuration = $this->prophesize(ImportConfiguration::class);

        $urls->getProviderForConfiguration($configuration->reveal())->willReturn($urlProvider->reveal());
        $urlProvider->getUrls()->willReturn([
            'https://example.com/resources/34343-ex',
        ]);

        $fetchData->jsonLDFromUrl('https://example.com/resources/34343-ex')->willReturn(['@graph' => [
            [
                '@id' => 'https://example.com/resources/34343-ex',
                '@type' => [
                    'schema:Organization',
                    'thuecat:TouristMarketingCompany',
                ],
            ],
        ]]);

        $converter->getConverterBasedOnType([
            'schema:Organization',
            'thuecat:TouristMarketingCompany',
        ])->willReturn(null);

        $saveData->import()->shouldNotBeCalled();

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        $subject->importConfiguration($configuration->reveal());
    }

    /**
     * @test
     */
    public function handlesEmptyResponse(): void
    {
        $urls = $this->prophesize(UrlProviderRegistry::class);
        $urlProvider = $this->prophesize(UrlProvider::class);
        $converter = $this->prophesize(ConverterRegistry::class);
        $concreteConverter = $this->prophesize(Converter::class);
        $importLogRepository = $this->prophesize(ImportLogRepository::class);
        $fetchData = $this->prophesize(FetchData::class);
        $saveData = $this->prophesize(SaveData::class);
        $configuration = $this->prophesize(ImportConfiguration::class);

        $urls->getProviderForConfiguration($configuration->reveal())->willReturn($urlProvider->reveal());
        $urlProvider->getUrls()->willReturn([
            'https://example.com/resources/34343-ex',
        ]);

        $fetchData->jsonLDFromUrl('https://example.com/resources/34343-ex')->willReturn([]);

        $converter->getConverterBasedOnType()->shouldNotBeCalled();

        $subject = new Importer(
            $urls->reveal(),
            $converter->reveal(),
            $importLogRepository->reveal(),
            $fetchData->reveal(),
            $saveData->reveal()
        );
        $subject->importConfiguration($configuration->reveal());
    }
}
