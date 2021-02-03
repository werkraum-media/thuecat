<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD;

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

use WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser\OpeningHours;

class Parser
{
    private OpeningHours $openingHours;

    public function __construct(
        OpeningHours $openingHours
    ) {
        $this->openingHours = $openingHours;
    }
    public function getId(array $jsonLD): string
    {
        return $jsonLD['@id'];
    }

    public function getTitle(array $jsonLD, string $language = ''): string
    {
        return $this->getValueForLanguage($jsonLD['schema:name'], $language);
    }

    public function getDescription(array $jsonLD, string $language = ''): string
    {
        return $this->getValueForLanguage($jsonLD['schema:description'], $language);
    }

    public function getManagerId(array $jsonLD): string
    {
        return $jsonLD['thuecat:contentResponsible']['@id'];
    }

    /**
     * @return string[]
     */
    public function getContainedInPlaceIds(array $jsonLD): array
    {
        return array_map(function (array $place) {
            return $place['@id'];
        }, $jsonLD['schema:containedInPlace']);
    }

    public function getOpeningHours(array $jsonLD): array
    {
        return $this->openingHours->get($jsonLD);
    }

    /**
     * @return string[]
     */
    public function getLanguages(array $jsonLD): array
    {
        if (isset($jsonLD['schema:availableLanguage']) === false) {
            return [];
        }

        $languages = $jsonLD['schema:availableLanguage'];

        $languages = array_filter($languages, function (array $language) {
            return isset($language['@type'])
                && $language['@type'] === 'thuecat:Language'
                ;
        });

        $languages = array_map(function (array $language) {
            $language = $language['@value'];

            if ($language === 'thuecat:German') {
                return 'de';
            }
            if ($language === 'thuecat:English') {
                return 'en';
            }
            if ($language === 'thuecat:French') {
                return 'fr';
            }

            throw new \Exception('Unsupported language "' . $language . '".', 1612367481);
        }, $languages);

        return $languages;
    }

    private function getValueForLanguage(
        array $property,
        string $language
    ): string {
        if (
            $this->doesLanguageMatch($property, $language)
            && isset($property['@value'])
        ) {
            return $property['@value'];
        }

        foreach ($property as $languageEntry) {
            if (
                is_array($languageEntry)
                && $this->doesLanguageMatch($languageEntry, $language)
            ) {
                return $languageEntry['@value'];
            }
        }

        return '';
    }

    private function doesLanguageMatch(array $property, string $language): bool
    {
        return isset($property['@language'])
            && (
                $property['@language'] === $language
                || $language === ''
            )
            ;
    }
}
