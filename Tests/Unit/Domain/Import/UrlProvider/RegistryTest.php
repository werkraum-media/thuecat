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
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\UrlProvider\Registry
 */
class RegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new Registry();

        self::assertInstanceOf(Registry::class, $subject);
    }

    /**
     * @test
     */
    public function allowsRegistrationOfUrlProvider(): void
    {
        $subject = new Registry();
        $provider = $this->prophesize(UrlProvider::class);

        $subject->registerProvider($provider->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function returnsNullIfNoProviderExistsForConfiguration(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);

        $subject = new Registry();

        $result = $subject->getProviderForConfiguration($configuration->reveal());
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function returnsProviderForConfiguration(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);

        $subject = new Registry();

        $provider = $this->prophesize(UrlProvider::class);
        $concreteProvider = $this->prophesize(UrlProvider::class);
        $provider->canProvideForConfiguration($configuration->reveal())->willReturn(true);
        $provider->createWithConfiguration($configuration->reveal())->willReturn($concreteProvider->reveal());
        $subject->registerProvider($provider->reveal());

        $result = $subject->getProviderForConfiguration($configuration->reveal());
        self::assertSame($concreteProvider->reveal(), $result);
    }
}
