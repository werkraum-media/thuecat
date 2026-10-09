<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\EventEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristInformationEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TrailEntity;
use WerkraumMedia\ThueCat\Import\Settings\AnchorScope;
use WerkraumMedia\ThueCat\Import\Settings\AnchorScopeRegistry;
use WerkraumMedia\ThueCat\Tests\Unit\Import\Settings\Fixtures\ConflictingAttractionEntity;
use WerkraumMedia\ThueCat\Tests\Unit\Import\Settings\Fixtures\InheritingAttractionEntity;

class AnchorScopeRegistryTest extends TestCase
{
    #[Test]
    #[DataProvider('declaredScopes')]
    public function answersTheScopeTheEntityDeclares(string $table, string $expectedScope): void
    {
        self::assertSame($expectedScope, $this->createSubject()->forTable($table)?->value);
    }

    #[Test]
    #[DataProvider('scopelessTables')]
    public function answersNoScopeForAKindWithoutOne(string $table): void
    {
        self::assertNull($this->createSubject()->forTable($table));
    }

    #[Test]
    public function answersNoScopeForATableNoEntityWrites(): void
    {
        self::assertNull($this->createSubject()->forTable('tt_content'));
    }

    #[Test]
    public function listsEveryDeclaredScopeOnce(): void
    {
        $scopes = array_map(
            static fn (AnchorScope $scope): string => $scope->value,
            $this->createSubject()->scopes()
        );
        sort($scopes);

        self::assertSame(['events', 'thuecat', 'trails'], $scopes);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function declaredScopes(): array
    {
        return [
            'tourist attraction' => ['tx_thuecat_tourist_attraction', 'thuecat'],
            'event' => ['tx_events_domain_model_event', 'events'],
            'trail' => ['tx_thuecat_trail', 'trails'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scopelessTables(): array
    {
        return [
            'organisation' => ['tx_thuecat_organisation'],
            'tourist information' => ['tx_thuecat_tourist_information'],
        ];
    }

    #[Test]
    public function acceptsASubclassInheritingItsTablesScope(): void
    {
        $subject = $this->createSubject([InheritingAttractionEntity::class]);

        self::assertSame('thuecat', $subject->forTable('tx_thuecat_tourist_attraction')?->value);
    }

    /**
     * Service order would otherwise decide which tree the table's records land
     * in, silently and differently per installation.
     */
    #[Test]
    public function rejectsOneTableClaimedByTwoScopes(): void
    {
        $subject = $this->createSubject([ConflictingAttractionEntity::class]);

        // Caught here and asserted outside: PHPUnit's own failure is a
        // RuntimeException too, so a fail() inside the try would be swallowed.
        $exception = null;
        try {
            $subject->forTable('tx_thuecat_tourist_attraction');
        } catch (RuntimeException $caught) {
            $exception = $caught;
        }

        self::assertNotNull($exception, 'Two scopes for one table must be rejected.');
        self::assertSame(1790929291, $exception->getCode());
        self::assertStringContainsString('tx_thuecat_tourist_attraction', $exception->getMessage());
        self::assertStringContainsString('thuecat', $exception->getMessage());
        self::assertStringContainsString('conflicting', $exception->getMessage());
    }

    /**
     * Keyed by class name, as the container registers the tagged entities. The
     * factories are never called: the scope is read statically.
     *
     * @param list<class-string> $additionalEntities
     */
    private function createSubject(array $additionalEntities = []): AnchorScopeRegistry
    {
        $factories = [];
        foreach ([
            TouristAttractionEntity::class,
            EventEntity::class,
            TrailEntity::class,
            OrganisationEntity::class,
            TouristInformationEntity::class,
            ...$additionalEntities,
        ] as $class) {
            $factories[$class] = static fn () => throw new RuntimeException('Not to be instantiated.', 1790924770);
        }

        return new AnchorScopeRegistry(new ServiceLocator($factories));
    }
}
