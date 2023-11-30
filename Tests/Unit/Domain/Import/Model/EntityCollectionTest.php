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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Model\Entity;
use WerkraumMedia\ThueCat\Domain\Import\Model\EntityCollection;

class EntityCollectionTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $subject = new EntityCollection();

        self::assertInstanceOf(EntityCollection::class, $subject);
    }

    #[Test]
    public function returnsEmptyArrayAsDefaultEntities(): void
    {
        $subject = new EntityCollection();

        self::assertSame([], $subject->getEntities());
    }

    #[Test]
    public function returnsFirstEntityForDefaultLanguage(): void
    {
        $entityWithTranslation = $this->createStub(Entity::class);
        $entityWithTranslation->method('isForDefaultLanguage')->willReturn(false);

        $entityWithDefaultLanguage = $this->createStub(Entity::class);
        $entityWithDefaultLanguage->method('isForDefaultLanguage')->willReturn(true);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation);
        $subject->add($entityWithDefaultLanguage);

        self::assertSame(
            $entityWithDefaultLanguage,
            $subject->getDefaultLanguageEntity()
        );
    }

    #[Test]
    public function returnsNullIfNoEntityForDefaultLanguageExists(): void
    {
        $entityWithTranslation = $this->createStub(Entity::class);
        $entityWithTranslation->method('isForDefaultLanguage')->willReturn(false);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation);

        self::assertNull(
            $subject->getDefaultLanguageEntity()
        );
    }

    #[Test]
    public function returnsEntitiesToTranslate(): void
    {
        $entityWithTranslation = $this->createStub(Entity::class);
        $entityWithTranslation->method('isTranslation')->willReturn(true);
        $entityWithTranslation->method('exists')->willReturn(false);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation);

        self::assertSame(
            [
                $entityWithTranslation,
            ],
            $subject->getEntitiesToTranslate()
        );
    }

    #[Test]
    public function returnsExistingEntities(): void
    {
        $entityWithTranslation = $this->createStub(Entity::class);
        $entityWithTranslation->method('exists')->willReturn(true);

        $subject = new EntityCollection();
        $subject->add($entityWithTranslation);

        self::assertSame(
            [
                $entityWithTranslation,
            ],
            $subject->getExistingEntities()
        );
    }
}
