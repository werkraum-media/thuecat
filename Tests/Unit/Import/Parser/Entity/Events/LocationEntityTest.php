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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\Events;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\Events\Domain\Model\Location;
use WerkraumMedia\ThueCat\Import\Parser\Entity\Events\LocationEntity;

class LocationEntityTest extends TestCase
{
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new LocationEntity();

        self::assertSame('tx_events_domain_model_location', $entity::TABLE);
    }

    /**
     * The guard on the duplicated hash.
     *
     * LocationEntity reproduces ext:events' Location::generateGlobalId()
     * because that method is private and its constructor demands a language
     * uid the parse stage has no access to. Duplication is only safe while the
     * two agree, so the hash is compared against one the model itself computed
     * rather than against a literal.
     *
     * If ext:events reorders, adds or drops a hashed field, this fails. Without
     * it the same change would silently fork every venue into a second row on
     * the next import — the failure mode the whole hash-identity design exists
     * to avoid.
     */
    #[Test]
    public function hashMatchesTheOneExtEventsComputes(): void
    {
        $entity = new LocationEntity();
        $entity->configure([
            'schema:name' => ['@language' => 'de', '@value' => 'Messe Erfurt'],
            'schema:address' => [
                'schema:streetAddress' => ['@language' => 'de', '@value' => 'Gothaer Straße 34'],
                'schema:postalCode' => ['@language' => 'de', '@value' => '99094'],
                'schema:addressLocality' => ['@language' => 'de', '@value' => 'Erfurt'],
                'schema:addressCountry' => ['@language' => 'de', '@value' => 'Deutschland'],
            ],
        ], 'de');

        $model = new Location(
            'Messe Erfurt',
            'Gothaer Straße 34',
            '99094',
            'Erfurt',
            // ThueCat never supplies a district, and it feeds the hash.
            '',
            'Deutschland',
            '',
            '',
            '',
            0
        );

        self::assertSame($model->getGlobalId(), $entity->getGlobalId());
    }

    /**
     * Country feeds the hash, so the CURIE encoding must arrive at the literal
     * before it is hashed — otherwise the 140 CURIE-encoded venues in the
     * survey each fork into a row of their own.
     */
    #[Test]
    public function curieCountryHashesAsTheLiteral(): void
    {
        self::assertSame(
            $this->globalIdForCountry(['@language' => 'de', '@value' => 'Deutschland']),
            $this->globalIdForCountry(['@type' => 'thuecat:AddressCountry', '@value' => 'thuecat:Germany'])
        );
    }

    /**
     * A node with nothing usable must not become a row: every contentless
     * venue would otherwise hash alike and collect onto one shared location.
     */
    #[Test]
    public function venueWithoutAnyValueIsNotValid(): void
    {
        $entity = new LocationEntity();
        $entity->configure([], 'de');

        self::assertFalse($entity->isValid());
    }

    #[Test]
    public function venueCarryingOnlyANameIsValid(): void
    {
        $entity = new LocationEntity();
        $entity->configure(['schema:name' => ['@language' => 'de', '@value' => 'Messe Erfurt']], 'de');

        self::assertTrue($entity->isValid());
    }

    /** @param array<string, mixed> $country */
    private function globalIdForCountry(array $country): string
    {
        $entity = new LocationEntity();
        $entity->configure([
            'schema:name' => ['@language' => 'de', '@value' => 'Messe Erfurt'],
            'schema:address' => [
                'schema:postalCode' => ['@language' => 'de', '@value' => '99094'],
                'schema:addressCountry' => $country,
            ],
        ], 'de');

        return $entity->getGlobalId();
    }
}
