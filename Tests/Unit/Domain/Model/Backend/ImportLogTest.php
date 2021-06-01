<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Backend;

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
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use PHPUnit\Framework\TestCase;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog
 */
class ImportLogTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new ImportLog();

        self::assertInstanceOf(ImportLog::class, $subject);
    }

    /**
     * @test
     */
    public function returnsConfigurationIfSet(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);
        $subject = new ImportLog($configuration->reveal());

        self::assertSame($configuration->reveal(), $subject->getConfiguration());
    }

    /**
     * @test
     */
    public function returnsNullForConfigurationIfNotSet(): void
    {
        $subject = new ImportLog();

        self::assertNull($subject->getConfiguration());
    }

    /**
     * @test
     */
    public function returnsConfigurationUidIfSet(): void
    {
        $configuration = $this->prophesize(ImportConfiguration::class);
        $configuration->getUid()->willReturn(10);
        $subject = new ImportLog($configuration->reveal());

        self::assertSame(10, $subject->getConfigurationUid());
    }

    /**
     * @test
     */
    public function returnsZeroForConfigurationIfNotSet(): void
    {
        $subject = new ImportLog();

        self::assertSame(0, $subject->getConfigurationUid());
    }
}
