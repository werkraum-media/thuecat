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
use WerkraumMedia\ThueCat\Domain\Import\RequestFactory;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\RequestFactory
 */
class RequestFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new RequestFactory();

        self::assertInstanceOf(RequestFactory::class, $subject);
    }

    /**
     * @test
     */
    public function returnsRequestWithJsonIdFormat(): void
    {
        $subject = new RequestFactory();
        $request = $subject->createRequest('GET', 'https://example.com/resources/333039283321-xxwg');

        self::assertSame('format=jsonId', $request->getUri()->getQuery());
    }
}
