<?php

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\UrlProvider;

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

use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\SyncScopeUrlProvider;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\SyncScopeUrlProvider
 */
class SyncScopeUrlProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $fetchData = $this->prophesize(FetchData::class);

        $subject = new SyncScopeUrlProvider(
            $fetchData->reveal()
        );

        self::assertInstanceOf(SyncScopeUrlProvider::class, $subject);
    }

    /**
     * @test
     */
    public function canProvideForSyncScope(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getType()->willReturn('syncScope');

        $fetchData = $this->prophesize(FetchData::class);

        $subject = new SyncScopeUrlProvider(
            $fetchData->reveal()
        );

        $result = $subject->canProvideForConfiguration($configuration->reveal());
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function returnsConcreteProviderForConfiguration(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getSyncScopeId()->willReturn(10);

        $fetchData = $this->prophesize(FetchData::class);
        $fetchData->updatedNodes(10)->willReturn([
            'data' => [
                'canBeCreated' => [
                    '835224016581-dara',
                    '165868194223-zmqf',
                ],
            ],
        ]);

        $subject = new SyncScopeUrlProvider(
            $fetchData->reveal()
        );

        $result = $subject->createWithConfiguration($configuration->reveal());

        self::assertInstanceOf(SyncScopeUrlProvider::class, $result);
    }

    /**
     * @test
     */
    public function concreteProviderReturnsUrls(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getSyncScopeId()->willReturn(10);

        $fetchData = $this->prophesize(FetchData::class);
        $fetchData->getResourceEndpoint()->willReturn('https://example.com/api/');
        $fetchData->updatedNodes(10)->willReturn([
            'data' => [
                'createdOrUpdated' => [
                    '835224016581-dara',
                    '165868194223-zmqf',
                ],
            ],
        ]);

        $subject = new SyncScopeUrlProvider(
            $fetchData->reveal()
        );

        $concreteProvider = $subject->createWithConfiguration($configuration->reveal());
        $result = $concreteProvider->getUrls();

        self::assertSame([
            'https://example.com/api/835224016581-dara',
            'https://example.com/api/165868194223-zmqf',
        ], $result);
    }
}
