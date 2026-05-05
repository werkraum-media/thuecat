<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity\TransientEntity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity\OfferEntity;

class OfferEntityTest extends TestCase
{
    #[Test]
    public function shapesOfferWithNestedPriceSpecificationList(): void
    {
        $node = [
            '@type' => ['schema:Intangible', 'schema:Thing', 'schema:Offer'],
            'schema:name' => ['@language' => 'de', '@value' => 'Führungen'],
            'schema:description' => ['@language' => 'de', '@value' => 'Immer samstags, um 11:15 Uhr'],
            'thuecat:offerType' => ['@type' => 'thuecat:OfferType', '@value' => 'thuecat:GuidedTourOffer'],
            'schema:priceSpecification' => [
                [
                    'schema:name' => ['@language' => 'de', '@value' => 'Erwachsene'],
                    'schema:price' => ['@type' => 'schema:Number', '@value' => '8'],
                    'schema:priceCurrency' => ['@type' => 'thuecat:Currency', '@value' => 'thuecat:EUR'],
                    'thuecat:calculationRule' => ['@type' => 'thuecat:CalculationRule', '@value' => 'thuecat:PerPerson'],
                ],
                [
                    'schema:name' => ['@language' => 'de', '@value' => 'Ermäßigt'],
                    'schema:description' => ['@language' => 'de', '@value' => 'als ermäßigt gelten …'],
                    'schema:price' => ['@type' => 'schema:Number', '@value' => '5'],
                    'schema:priceCurrency' => ['@type' => 'thuecat:Currency', '@value' => 'thuecat:EUR'],
                    'thuecat:calculationRule' => ['@type' => 'thuecat:CalculationRule', '@value' => 'thuecat:PerPerson'],
                ],
            ],
        ];

        $entity = new OfferEntity();
        $entity->configure($node, 'de');

        self::assertSame([
            'types' => ['GuidedTourOffer'],
            'title' => 'Führungen',
            'description' => 'Immer samstags, um 11:15 Uhr',
            'prices' => [
                [
                    'title' => 'Erwachsene',
                    'description' => '',
                    'price' => 8.00,
                    'currency' => 'EUR',
                    'rule' => 'PerPerson',
                ],
                [
                    'title' => 'Ermäßigt',
                    'description' => 'als ermäßigt gelten …',
                    'price' => 5.00,
                    'currency' => 'EUR',
                    'rule' => 'PerPerson',
                ],
            ],
        ], $entity->toArray());
    }

    #[Test]
    public function acceptsSinglePriceSpecificationObjectAsWellAsList(): void
    {
        $node = [
            'schema:name' => ['@language' => 'de', '@value' => 'Eintritt'],
            'thuecat:offerType' => ['@type' => 'thuecat:OfferType', '@value' => 'thuecat:EntryOffer'],
            'schema:priceSpecification' => [
                'schema:name' => ['@language' => 'de', '@value' => 'Familienkarte'],
                'schema:price' => ['@type' => 'schema:Number', '@value' => '17'],
                'schema:priceCurrency' => ['@type' => 'thuecat:Currency', '@value' => 'thuecat:EUR'],
                'thuecat:calculationRule' => ['@type' => 'thuecat:CalculationRule', '@value' => 'thuecat:PerGroup'],
            ],
        ];

        $entity = new OfferEntity();
        $entity->configure($node, 'de');

        self::assertCount(1, $entity->toArray()['prices']);
        self::assertSame('Familienkarte', $entity->toArray()['prices'][0]['title']);
        self::assertSame('PerGroup', $entity->toArray()['prices'][0]['rule']);
    }

    #[Test]
    public function emitsEmptyStringsForPricesWithoutMatchingTranslation(): void
    {
        // Golden assertion for sys_language_uid=1 (English) shows empty strings
        // for price title/description when the source JSON only carries German.
        // The non-default row's blob therefore must NOT fall back to German.
        $node = [
            'schema:name' => ['@language' => 'de', '@value' => 'Eintritt'],
            'thuecat:offerType' => ['@type' => 'thuecat:OfferType', '@value' => 'thuecat:EntryOffer'],
            'schema:priceSpecification' => [
                [
                    'schema:name' => ['@language' => 'de', '@value' => 'Erwachsene'],
                    'schema:price' => ['@type' => 'schema:Number', '@value' => '14.90'],
                    'schema:priceCurrency' => ['@type' => 'thuecat:Currency', '@value' => 'thuecat:EUR'],
                    'thuecat:calculationRule' => ['@type' => 'thuecat:CalculationRule', '@value' => 'thuecat:PerPackage'],
                ],
            ],
        ];

        $entity = new OfferEntity();
        $entity->configure($node, 'en');

        $result = $entity->toArray();
        self::assertSame('', $result['title']);
        self::assertSame('', $result['prices'][0]['title']);
        self::assertSame('', $result['prices'][0]['description']);
        // Numeric and enum fields stay language-independent.
        self::assertSame(14.90, $result['prices'][0]['price']);
        self::assertSame('EUR', $result['prices'][0]['currency']);
        self::assertSame('PerPackage', $result['prices'][0]['rule']);
    }

    #[Test]
    public function omitsOfferTypeFromTypesWhenAbsent(): void
    {
        $node = [
            'schema:name' => ['@language' => 'de', '@value' => 'Sonderaktion'],
        ];

        $entity = new OfferEntity();
        $entity->configure($node, 'de');

        self::assertSame([], $entity->toArray()['types']);
        self::assertSame([], $entity->toArray()['prices']);
    }
}
