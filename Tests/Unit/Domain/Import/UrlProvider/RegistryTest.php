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
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\StaticUrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

class RegistryTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $subject = new Registry();

        self::assertInstanceOf(Registry::class, $subject);
    }

    #[Test]
    public function allowsRegistrationOfUrlProvider(): void
    {
        $subject = new Registry();
        $provider = new StaticUrlProvider();

        $subject->registerProvider($provider);
        self::assertTrue(true);
    }

    #[Test]
    public function returnsNullIfNoProviderExistsForConfiguration(): void
    {
        $configuration = new ImportConfiguration();

        $subject = new Registry();

        $result = $subject->getProviderForConfiguration($configuration);
        self::assertNull($result);
    }

    #[Test]
    public function returnsProviderForConfiguration(): void
    {
        $configuration = new ImportConfiguration();
        $configuration->_setProperty('type', 'static');
        $configuration->_setProperty('urls', ['https://example.com/path/example.json']);

        $subject = new Registry();

        $provider = new StaticUrlProvider();
        $subject->registerProvider($provider);

        $result = $subject->getProviderForConfiguration($configuration);
        self::assertInstanceOf(StaticUrlProvider::class, $result);
        self::assertSame(['https://example.com/path/example.json'], $result->getUrls());
    }
}
