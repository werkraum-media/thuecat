<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Model;

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
use WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\Model\GenericEntity
 */
class GenericEntityTest extends TestCase
{
    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );
        self::assertInstanceOf(GenericEntity::class, $subject);
    }

    /**
     * @test
     */
    public function returnsTypo3StoragePid(): void
    {
        $subject = new GenericEntity(
            10,
            '',
            0,
            '',
            []
        );
        self::assertSame(10, $subject->getTypo3StoragePid());
    }

    /**
     * @test
     */
    public function returnsTypo3DatabaseTableName(): void
    {
        $subject = new GenericEntity(
            0,
            'tx_thuecat_entity',
            0,
            '',
            []
        );
        self::assertSame('tx_thuecat_entity', $subject->getTypo3DatabaseTableName());
    }

    /**
     * @test
     */
    public function returnsTypo3SystemLanguageUid(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            10,
            '',
            []
        );
        self::assertSame(10, $subject->getTypo3SystemLanguageUid());
    }

    /**
     * @test
     */
    public function claimsIsForDefaultLanguage(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );
        self::assertTrue($subject->isForDefaultLanguage());
    }

    /**
     * @test
     */
    public function claimsIsTranslation(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            10,
            '',
            []
        );
        self::assertTrue($subject->isTranslation());
    }

    /**
     * @test
     */
    public function returnsRemoteId(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            'https://example.com/resources/333039283321-xxwg',
            []
        );
        self::assertSame(
            'https://example.com/resources/333039283321-xxwg',
            $subject->getRemoteId()
        );
    }

    /**
     * @test
     */
    public function returnsData(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            [
                'column_name_1' => 'value 1',
                'column_name_2' => 'value 2',
            ]
        );
        self::assertSame(
            [
                'column_name_1' => 'value 1',
                'column_name_2' => 'value 2',
            ],
            $subject->getData()
        );
    }

    /**
     * @test
     */
    public function returnsNotCreatedByDefault(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );
        self::assertFalse(
            $subject->wasCreated()
        );
    }

    /**
     * @test
     */
    public function returnsNotExistingByDefault(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );
        self::assertFalse(
            $subject->exists()
        );
    }

    /**
     * @test
     */
    public function returnsZeroAsDefaultTypo3Uid(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );
        self::assertSame(
            0,
            $subject->getTypo3Uid()
        );
    }

    /**
     * @test
     */
    public function canBeMarkedAsImported(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );

        $subject->setImportedTypo3Uid(10);
        self::assertTrue($subject->wasCreated());
        self::assertTrue($subject->exists());
        self::assertSame(10, $subject->getTypo3Uid());
    }

    /**
     * @test
     */
    public function canBeMarkedAsExisting(): void
    {
        $subject = new GenericEntity(
            0,
            '',
            0,
            '',
            []
        );

        $subject->setExistingTypo3Uid(10);
        self::assertFalse($subject->wasCreated());
        self::assertTrue($subject->exists());
        self::assertSame(10, $subject->getTypo3Uid());
    }
}
