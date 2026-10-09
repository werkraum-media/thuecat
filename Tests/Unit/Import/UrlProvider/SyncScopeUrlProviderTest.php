<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\UrlProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Import\UrlProvider\SyncScopeUrlProvider;

class SyncScopeUrlProviderTest extends TestCase
{
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
