<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\SyncScopeUrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

class SyncScopeUrlProviderTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $fetchData = self::createStub(FetchData::class);

        $subject = new SyncScopeUrlProvider(
            $fetchData
        );

        self::assertInstanceOf(SyncScopeUrlProvider::class, $subject);
    }

    #[Test]
    public function canProvideForSyncScope(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'syncScope');

        $fetchData = self::createStub(FetchData::class);

        $subject = new SyncScopeUrlProvider(
            $fetchData
        );

        $result = $subject->canProvideForConfiguration($configuration);
        self::assertTrue($result);
    }

    #[Test]
    public function returnsConcreteProviderForConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('syncScopeId', 10);

        $fetchData = self::createStub(FetchData::class);
        $fetchData->method('updatedNodes')->willReturn([
            'data' => [
                'canBeCreated' => [
                    '835224016581-dara',
                    '165868194223-zmqf',
                ],
            ],
        ]);

        $subject = new SyncScopeUrlProvider(
            $fetchData
        );

        $result = $subject->createWithConfiguration($configuration);

        self::assertInstanceOf(SyncScopeUrlProvider::class, $result);
    }

    #[Test]
    public function concreteProviderReturnsUrls(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('syncScopeId', 10);

        $fetchData = self::createStub(FetchData::class);
        $fetchData->method('getFullResourceUrl')->willReturnOnConsecutiveCalls(
            'https://example.com/api/835224016581-dara',
            'https://example.com/api/165868194223-zmqf'
        );
        $fetchData->method('updatedNodes')->willReturn([
            'data' => [
                'createdOrUpdated' => [
                    '835224016581-dara',
                    '165868194223-zmqf',
                ],
            ],
        ]);

        $subject = new SyncScopeUrlProvider(
            $fetchData
        );

        $concreteProvider = $subject->createWithConfiguration($configuration);
        $result = $concreteProvider->getUrls();

        self::assertSame([
            'https://example.com/api/835224016581-dara',
            'https://example.com/api/165868194223-zmqf',
        ], $result);
    }
}
