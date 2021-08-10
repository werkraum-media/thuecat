<?php

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

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class Offers
{
    /**
     * @var GenericFields
     */
    private $genericFields;

    public function __construct(
        GenericFields $genericFields
    ) {
        $this->genericFields = $genericFields;
    }

    public function get(array $jsonLD, SiteLanguage $language): array
    {
        $offers = [];
        $jsonLDOffers = $jsonLD['schema:makesOffer'] ?? [];

        if (isset($jsonLDOffers['@id'])) {
            return [
                $this->getOffer($jsonLDOffers, $language),
            ];
        }

        foreach ($jsonLDOffers as $jsonLDOffer) {
            $offer = $this->getOffer($jsonLDOffer, $language);
            if ($offer !== []) {
                $offers[] = $offer;
            }
        }

        return $offers;
    }

    private function getOffer(array $jsonLD, SiteLanguage $language): array
    {
        return [
            'title' => $this->genericFields->getTitle($jsonLD, $language),
            'description' => $this->genericFields->getDescription($jsonLD, $language),
            'prices' => $this->getPrices($jsonLD, $language),
        ];
    }

    private function getPrices(array $jsonLD, SiteLanguage $language): array
    {
        $prices = [];
        $jsonLDPrices = $jsonLD['schema:priceSpecification'] ?? [];

        if (isset($jsonLDPrices['@id'])) {
            return [
                $this->getPrice($jsonLDPrices, $language),
            ];
        }

        foreach ($jsonLDPrices as $jsonLDPrice) {
            $price = $this->getPrice($jsonLDPrice, $language);
            if ($price !== []) {
                $prices[] = $price;
            }
        }

        return $prices;
    }

    private function getPrice(array $jsonLD, SiteLanguage $language): array
    {
        return [
            'title' => $this->genericFields->getTitle($jsonLD, $language),
            'description' => $this->genericFields->getDescription($jsonLD, $language),
            'price' => $this->getPriceValue($jsonLD),
            'currency' => $this->getCurrencyValue($jsonLD),
            'rule' => $this->getRuleValue($jsonLD),
        ];
    }

    private function getPriceValue(array $jsonLD): float
    {
        $price = $jsonLD['schema:price']['@value'] ?? 0.0;
        $price = floatval($price);
        return $price;
    }

    private function getCurrencyValue(array $jsonLD): string
    {
        $currency = $jsonLD['schema:priceCurrency']['@value'] ?? '';
        $currency = str_replace('thuecat:', '', $currency);
        return $currency;
    }

    private function getRuleValue(array $jsonLD): string
    {
        $rule = $jsonLD['thuecat:calculationRule']['@value'] ?? '';
        $rule = str_replace('thuecat:', '', $rule);
        return $rule;
    }
}
