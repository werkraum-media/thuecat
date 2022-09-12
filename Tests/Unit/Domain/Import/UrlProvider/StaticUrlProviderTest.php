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

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\StaticUrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\StaticUrlProvider
 */
class StaticUrlProviderTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new StaticUrlProvider();
        self::assertInstanceOf(StaticUrlProvider::class, $subject);
    }

    /**
     * @test
     */
    public function canProvideForStaticConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'static');

        $subject = new StaticUrlProvider();

        $result = $subject->canProvideForConfiguration($configuration);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function returnsConcreteProviderForConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', ['https://example.com']);

        $subject = new StaticUrlProvider();

        $result = $subject->createWithConfiguration($configuration);
        self::assertInstanceOf(StaticUrlProvider::class, $result);
    }

    /**
     * @test
     */
    public function concreteProviderReturnsUrls(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('urls', ['https://example.com']);

        $subject = new StaticUrlProvider();

        $concreteProvider = $subject->createWithConfiguration($configuration);
        $result = $concreteProvider->getUrls();
        self::assertSame([
            'https://example.com',
        ], $result);
    }
}
