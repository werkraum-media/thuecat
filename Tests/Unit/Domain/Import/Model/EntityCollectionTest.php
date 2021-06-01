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
use Prophecy\PhpUnit\ProphecyTrait;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;

/**
 * @covers \WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection
 */
class EntityCollectionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $subject = new EntityCollection();

        self::assertInstanceOf(EntityCollection::class, $subject);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayAsDefaultEntities(): void
    {
        $subject = new EntityCollection();

        self::assertSame([], $subject->getEntities());
    }

    /**
     * @test
     */
    public function returnsFirstEntityForDefaultLanguage(): void
    {
        $entityWithTranslation = $this->prophesize(Entity::class);
        $entityWithTranslation->isForDefaultLanguage()->willReturn(false);

        $entityWithDefaultLanguage = $this->prophesize(Entity::class);
        $entityWithDefaultLanguage->isForDefaultLanguage()->willReturn(true);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation->reveal());
        $subject->add($entityWithDefaultLanguage->reveal());

        self::assertSame(
            $entityWithDefaultLanguage->reveal(),
            $subject->getDefaultLanguageEntity()
        );
    }

    /**
     * @test
     */
    public function returnsNullIfNoEntityForDefaultLanguageExists(): void
    {
        $entityWithTranslation = $this->prophesize(Entity::class);
        $entityWithTranslation->isForDefaultLanguage()->willReturn(false);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation->reveal());

        self::assertNull(
            $subject->getDefaultLanguageEntity()
        );
    }

    /**
     * @test
     */
    public function returnsEntitiesToTranslate(): void
    {
        $entityWithTranslation = $this->prophesize(Entity::class);
        $entityWithTranslation->isTranslation()->willReturn(true);
        $entityWithTranslation->exists()->willReturn(false);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation->reveal());

        self::assertSame(
            [
                $entityWithTranslation->reveal(),
            ],
            $subject->getEntitiesToTranslate()
        );
    }

    /**
     * @test
     */
    public function returnsExistingEntities(): void
    {
        $entityWithTranslation = $this->prophesize(Entity::class);
        $entityWithTranslation->exists()->willReturn(true);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation->reveal());

        self::assertSame(
            [
                $entityWithTranslation->reveal(),
            ],
            $subject->getExistingEntities()
        );
    }
}
