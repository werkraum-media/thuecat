<?php

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\JsonLD\Parser;

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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\GenericFields;
use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Offers;

/**
 * @covers WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\Offers
 */
class OffersTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function canBeCreated(): void
    {
        $genericFields = $this->prophesize(GenericFields::class);

        $subject = new Offers(
            $genericFields->reveal()
        );

        self::assertInstanceOf(Offers::class, $subject);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayIfNoOfferExists(): void
    {
        $siteLanguage = $this->prophesize(SiteLanguage::class);
        $genericFields = $this->prophesize(GenericFields::class);

        $subject = new Offers(
            $genericFields->reveal()
        );

        $result = $subject->get([], $siteLanguage->reveal());

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function returnsMultipleOfferWithMultiplePrices(): void
    {
        $jsonLD = [
            'schema:makesOffer' => [
                [
                    '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b5',
                    '@type' => [
                        0 => 'schema:Intangible',
                        1 => 'schema:Thing',
                        2 => 'schema:Offer',
                    ],
                    'rdfs:label' => [
                        '@language' => 'de',
                        '@value' => 'Führungen',
                    ],
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => 'Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten',
                    ],
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Führungen',
                    ],
                    'schema:offeredBy' => [
                        '@id' => 'https://thuecat.org/resources/165868194223-zmqf',
                    ],
                    'schema:priceSpecification' => [
                        [
                            '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b6',
                            '@type' => [
                                0 => 'schema:Intangible',
                                1 => 'schema:StructuredValue',
                                2 => 'schema:PriceSpecification',
                                3 => 'schema:Thing',
                            ],
                            'rdfs:label' => [
                                '@language' => 'de',
                                '@value' => 'Erwachsene',
                            ],
                            'schema:name' => [
                                '@language' => 'de',
                                '@value' => 'Erwachsene',
                            ],
                            'schema:price' => [
                                '@type' => 'schema:Number',
                                '@value' => '8',
                            ],
                            'schema:priceCurrency' => [
                                '@type' => 'thuecat:Currency',
                                '@value' => 'thuecat:EUR',
                            ],
                            'thuecat:calculationRule' => [
                                '@type' => 'thuecat:CalculationRule',
                                '@value' => 'thuecat:PerPerson',
                            ],
                        ],
                        [
                            '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b7',
                            '@type' => [
                                0 => 'schema:Intangible',
                                1 => 'schema:StructuredValue',
                                2 => 'schema:PriceSpecification',
                                3 => 'schema:Thing',
                            ],
                            'rdfs:label' => [
                                '@language' => 'de',
                                '@value' => 'Ermäßigt',
                            ],
                            'schema:description' => [
                                '@language' => 'de',
                                '@value' => 'als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt',
                            ],
                            'schema:name' => [
                                '@language' => 'de',
                                '@value' => 'Ermäßigt',
                            ],
                            'schema:price' => [
                                '@type' => 'schema:Number',
                                '@value' => '5',
                            ],
                            'schema:priceCurrency' => [
                                '@type' => 'thuecat:Currency',
                                '@value' => 'thuecat:EUR',
                            ],
                            'thuecat:calculationRule' => [
                                '@type' => 'thuecat:CalculationRule',
                                '@value' => 'thuecat:PerPerson',
                            ],
                        ],
                    ],
                    'thuecat:offerType' => [
                        '@type' => 'thuecat:OfferType',
                        '@value' => 'thuecat:GuidedTourOffer',
                    ],
                ],
                [
                    '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b8',
                    '@type' => [
                        0 => 'schema:Intangible',
                        1 => 'schema:Thing',
                        2 => 'schema:Offer',
                    ],
                    'rdfs:label' => [
                        '@language' => 'de',
                        '@value' => 'Eintritt',
                    ],
                    'schema:description' => [
                        '@language' => 'de',
                        '@value' => "Schulklassen und Kitagruppen im Rahmen des Unterrichts: Eintritt frei\nAn jedem ersten Dienstag im Monat: Eintritt frei",
                    ],
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Eintritt',
                    ],
                    'schema:offeredBy' => [
                        '@id' => 'https://thuecat.org/resources/165868194223-zmqf',
                    ],
                    'schema:priceSpecification' => [
                        [
                            '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b10',
                            '@type' => [
                                0 => 'schema:Intangible',
                                1 => 'schema:StructuredValue',
                                2 => 'schema:PriceSpecification',
                                3 => 'schema:Thing',
                            ],
                            'rdfs:label' => [
                                '@language' => 'de',
                                '@value' => 'Ermäßigt',
                            ],
                            'schema:description' => [
                                '@language' => 'de',
                                '@value' => 'als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt',
                            ],
                            'schema:name' => [
                                '@language' => 'de',
                                '@value' => 'Ermäßigt',
                            ],
                            'schema:price' => [
                                '@type' => 'schema:Number',
                                '@value' => '5',
                            ],
                            'schema:priceCurrency' => [
                                '@type' => 'thuecat:Currency',
                                '@value' => 'thuecat:EUR',
                            ],
                            'thuecat:calculationRule' => [
                                '@type' => 'thuecat:CalculationRule',
                                '@value' => 'thuecat:PerPerson',
                            ],
                        ],
                        [
                            '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b11',
                            '@type' => [
                                0 => 'schema:Intangible',
                                1 => 'schema:StructuredValue',
                                2 => 'schema:PriceSpecification',
                                3 => 'schema:Thing',
                            ],
                            'rdfs:label' => [
                                '@language' => 'de',
                                '@value' => 'Familienkarte',
                            ],
                            'schema:name' => [
                                '@language' => 'de',
                                '@value' => 'Familienkarte',
                            ],
                            'schema:price' => [
                                '@type' => 'schema:Number',
                                '@value' => '17',
                            ],
                            'schema:priceCurrency' => [
                                '@type' => 'thuecat:Currency',
                                '@value' => 'thuecat:EUR',
                            ],
                            'thuecat:calculationRule' => [
                                '@type' => 'thuecat:CalculationRule',
                                '@value' => 'thuecat:PerGroup',
                            ],
                        ],
                    ],
                    'thuecat:offerType' => [
                        '@type' => 'thuecat:OfferType',
                        '@value' => 'thuecat:EntryOffer',
                    ],
                ],
            ],
        ];

        $siteLanguage = $this->prophesize(SiteLanguage::class);
        $genericFields = $this->prophesize(GenericFields::class);

        // Offer 1
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][0],
            $siteLanguage->reveal()
        )->willReturn('Führungen');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][0],
            $siteLanguage->reveal()
        )->willReturn('Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten');
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][0]['schema:priceSpecification'][0],
            $siteLanguage->reveal()
        )->willReturn('Erwachsene');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][0]['schema:priceSpecification'][0],
            $siteLanguage->reveal()
        )->willReturn('');
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][0]['schema:priceSpecification'][1],
            $siteLanguage->reveal()
        )->willReturn('Ermäßigt');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][0]['schema:priceSpecification'][1],
            $siteLanguage->reveal()
        )->willReturn('als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt');

        // Offer2
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][1],
            $siteLanguage->reveal()
        )->willReturn('Eintritt');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][1],
            $siteLanguage->reveal()
        )->willReturn("Schulklassen und Kitagruppen im Rahmen des Unterrichts: Eintritt frei\nAn jedem ersten Dienstag im Monat: Eintritt frei");
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][1]['schema:priceSpecification'][0],
            $siteLanguage->reveal()
        )->willReturn('Ermäßigt');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][1]['schema:priceSpecification'][0],
            $siteLanguage->reveal()
        )->willReturn('als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt');
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'][1]['schema:priceSpecification'][1],
            $siteLanguage->reveal()
        )->willReturn('Familienkarte');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'][1]['schema:priceSpecification'][1],
            $siteLanguage->reveal()
        )->willReturn('');

        $subject = new Offers(
            $genericFields->reveal()
        );

        $result = $subject->get($jsonLD, $siteLanguage->reveal());

        self::assertSame([
            [
                'title' => 'Führungen',
                'description' => 'Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten',
                'prices' => [
                    [
                        'title' => 'Erwachsene',
                        'description' => '',
                        'price' => 8.0,
                        'currency' => 'EUR',
                        'rule' => 'PerPerson',
                    ],
                    [
                        'title' => 'Ermäßigt',
                        'description' => 'als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt',
                        'price' => 5.0,
                        'currency' => 'EUR',
                        'rule' => 'PerPerson',
                    ],
                ],
            ],
            [
                'title' => 'Eintritt',
                'description' => "Schulklassen und Kitagruppen im Rahmen des Unterrichts: Eintritt frei\nAn jedem ersten Dienstag im Monat: Eintritt frei",
                'prices' => [
                    [
                        'title' => 'Ermäßigt',
                        'description' => 'als ermäßigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt',
                        'price' => 5.0,
                        'currency' => 'EUR',
                        'rule' => 'PerPerson',
                    ],
                    [
                        'title' => 'Familienkarte',
                        'description' => '',
                        'price' => 17.0,
                        'currency' => 'EUR',
                        'rule' => 'PerGroup',
                    ],
                ],
            ],
        ], $result);
    }

    /**
     * @test
     */
    public function returnsSingleOfferWithSinglePrice(): void
    {
        $jsonLD = [
            'schema:makesOffer' => [
                '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b5',
                '@type' => [
                    0 => 'schema:Intangible',
                    1 => 'schema:Thing',
                    2 => 'schema:Offer',
                ],
                'rdfs:label' => [
                    '@language' => 'de',
                    '@value' => 'Führungen',
                ],
                'schema:description' => [
                    '@language' => 'de',
                    '@value' => 'Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten',
                ],
                'schema:name' => [
                    '@language' => 'de',
                    '@value' => 'Führungen',
                ],
                'schema:offeredBy' => [
                    '@id' => 'https://thuecat.org/resources/165868194223-zmqf',
                ],
                'schema:priceSpecification' => [
                    '@id' => 'genid-28b33237f71b41e3ad54a99e1da769b9-b6',
                    '@type' => [
                        0 => 'schema:Intangible',
                        1 => 'schema:StructuredValue',
                        2 => 'schema:PriceSpecification',
                        3 => 'schema:Thing',
                    ],
                    'rdfs:label' => [
                        '@language' => 'de',
                        '@value' => 'Erwachsene',
                    ],
                    'schema:name' => [
                        '@language' => 'de',
                        '@value' => 'Erwachsene',
                    ],
                    'schema:price' => [
                        '@type' => 'schema:Number',
                        '@value' => '8',
                    ],
                    'schema:priceCurrency' => [
                        '@type' => 'thuecat:Currency',
                        '@value' => 'thuecat:EUR',
                    ],
                    'thuecat:calculationRule' => [
                        '@type' => 'thuecat:CalculationRule',
                        '@value' => 'thuecat:PerPerson',
                    ],
                ],
                'thuecat:offerType' => [
                    '@type' => 'thuecat:OfferType',
                    '@value' => 'thuecat:GuidedTourOffer',
                ],
            ],
        ];

        $siteLanguage = $this->prophesize(SiteLanguage::class);
        $genericFields = $this->prophesize(GenericFields::class);

        $genericFields->getTitle(
            $jsonLD['schema:makesOffer'],
            $siteLanguage->reveal()
        )->willReturn('Führungen');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer'],
            $siteLanguage->reveal()
        )->willReturn('Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten');
        $genericFields->getTitle(
            $jsonLD['schema:makesOffer']['schema:priceSpecification'],
            $siteLanguage->reveal()
        )->willReturn('Erwachsene');
        $genericFields->getDescription(
            $jsonLD['schema:makesOffer']['schema:priceSpecification'],
            $siteLanguage->reveal()
        )->willReturn('');

        $subject = new Offers(
            $genericFields->reveal()
        );

        $result = $subject->get($jsonLD, $siteLanguage->reveal());

        self::assertSame([
            [
                'title' => 'Führungen',
                'description' => 'Immer samstags, um 11:15 Uhr findet eine öffentliche Führung durch das Museum statt. Dauer etwa 90 Minuten',
                'prices' => [
                    [
                        'title' => 'Erwachsene',
                        'description' => '',
                        'price' => 8.0,
                        'currency' => 'EUR',
                        'rule' => 'PerPerson',
                    ],
                ],
            ],
        ], $result);
    }
}
