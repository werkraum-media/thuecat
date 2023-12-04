<?php

declare(strict_types=1);

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

namespace WerkraumMedia\ThueCat\Domain\Import\EntityMapper;

use InvalidArgumentException;
use Symfony\Component\Serializer\Encoder\JsonDecode as SymfonyJsonDecode;

/**
 * Used to add further necessary normalization on decoding incoming JSON structure.
 *
 * See list of decode* methods to see what kind of data is normalized.
 */
class JsonDecode extends SymfonyJsonDecode
{
    final public const ACTIVE_LANGUAGE = 'active_language';

    /**
     * @var array[]
     */
    private array $rulesToKeepTypeInfo = [
        [
            'type' => 'beginsWith',
            'comparisonValue' => 'thuecat:facilityAccessibility',
        ],
    ];

    public function decode(
        string $data,
        string $format,
        array $context = []
    ): mixed {
        $context[self::ASSOCIATIVE] = true;
        $result = parent::decode($data, $format, $context);

        $activeLanguage = $context[self::ACTIVE_LANGUAGE] ?? '';
        if ($activeLanguage === '') {
            throw new InvalidArgumentException('Provide active language: ' . self::ACTIVE_LANGUAGE);
        }

        return $this->process(
            $result,
            $activeLanguage
        );
    }

    private function process(
        array &$array,
        string $activeLanguage
    ): array {
        foreach ($array as $key => $value) {
            $value = $this->decodeDateTime($value);
            $value = $this->decodeSingleValues($value);
            $value = $this->decodeLanguageSpecificValue($value, $activeLanguage);

            if (is_array($value)) {
                $value = $this->process($value, $activeLanguage);
            }

            $newKey = $this->mapKey($key);
            if ($newKey !== $key) {
                unset($array[$key]);
            }
            $array[$newKey] = $value;
        }

        return $array;
    }

    /**
     * Some properties might contain a list of value where each list entry is for a specific language.
     *
     * This decode will resolve the list to a single value based on current language settings from context.
     *
     *
     * @return mixed
     */
    private function decodeLanguageSpecificValue(
        mixed &$value,
        string $activeLanguage
    ) {
        if (is_array($value) === false) {
            return $value;
        }

        $newValue = $value['@value'] ?? null;
        $language = $value['@language'] ?? null;
        if (is_string($newValue) && $language === $activeLanguage) {
            return $newValue;
        }
        if (is_string($newValue) && is_string($language) && $language !== $activeLanguage) {
            return '';
        }

        $hasLanguageValue = false;
        if (ArrayDenormalizer::hasOnlyNumericKeys($value) === false) {
            return $value;
        }

        foreach ($value as $languageSpecific) {
            if (is_array($languageSpecific) === false) {
                continue;
            }

            $language = $languageSpecific['@language'] ?? '';
            if ($language === '') {
                continue;
            }

            if ($language === $activeLanguage) {
                $newValue = $languageSpecific['@value'] ?? null;
            }
            if (is_string($newValue)) {
                return $newValue;
            }

            if ($hasLanguageValue === false && isset($languageSpecific['@value'])) {
                $hasLanguageValue = true;
            }
        }

        // Prevent delivering original array if we detected this is language specific.
        // A string is then expected. But we didn't find any, so return empty one to conform to type.
        if ($hasLanguageValue) {
            return '';
        }

        return $value;
    }

    /**
     * Some properties might be an array with extra info.
     *
     * This decode will resolve single values wrapped in array with extra info.
     *
     *
     * @return mixed
     */
    private function decodeSingleValues(
        mixed &$value
    ) {
        if (is_array($value) === false) {
            return $value;
        }

        if (array_key_exists('@language', $value)) {
            return $value;
        }

        $type = $value['@type'] ?? null;
        if (is_string($type)) {
            foreach ($this->rulesToKeepTypeInfo as $rule) {
                if ($this->doesRuleMatch($rule, $type)) {
                    return $value;
                }
            }
        }

        $newValue = $value['@value'] ?? null;
        if (is_string($newValue)) {
            return $newValue;
        }

        return $value;
    }

    /**
     * Prepare data structure for PHP \DateTimeImmutable.
     *
     *
     * @return mixed
     */
    private function decodeDateTime(
        mixed &$value
    ) {
        $supportedTypes = [
            'schema:Time',
            'schema:Date',
        ];

        if (
            is_array($value) === false
            || isset($value['@type']) === false
            || isset($value['@value']) === false
            || in_array($value['@type'], $supportedTypes) === false
        ) {
            return $value;
        }

        return $value['@value'];
    }

    /**
     * @return mixed
     */
    private function mapKey(mixed $key)
    {
        if (is_string($key) === false) {
            return $key;
        }

        if (str_starts_with($key, '@')) {
            return mb_substr($key, 1);
        }
        if (str_starts_with($key, 'schema:')) {
            return mb_substr($key, 7);
        }
        if (str_starts_with($key, 'thuecat:')) {
            return mb_substr($key, 8);
        }

        return $key;
    }

    private function doesRuleMatch(array $rule, string $type): bool
    {
        if ($rule['type'] === 'beginsWith') {
            return str_starts_with($type, (string) $rule['comparisonValue']);
        }

        return false;
    }
}
