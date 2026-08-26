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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\OfferEntity;

/**
 * Builders shared by the POI record kinds: address, opening hours, offers and
 * distance to public transport.
 */
abstract class AbstractPlaceEntity extends AbstractEntity
{
    /**
     * schema:makesOffer is a single Offer node or a list of them.
     */
    protected function buildOffers(mixed $value, string $language): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $offers = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entity = new OfferEntity();
            $entity->configure($item, $language);
            $offers[] = $entity->toArray();
        }

        if ($offers === []) {
            return '';
        }

        return (string)(json_encode($offers) ?: '');
    }

    /**
     * Flatten thuecat:distanceToPublicTransport into "value:unit[:mean1:mean2]".
     */
    protected function buildDistanceToPublicTransport(mixed $node, string $language): string
    {
        if (!is_array($node)) {
            return '';
        }

        $distance = $this->extractValue($node['schema:value'] ?? null, $language);
        if ($distance === '') {
            return '';
        }

        $unit = $this->extractConcatenatedString($node['schema:unitCode'] ?? null, $language);
        $means = $this->extractConcatenatedMembers($node['thuecat:meansOfTransport'] ?? null, $language);

        $parts = array_merge([$distance, $unit], $means);

        return implode(':', array_filter($parts, static fn ($part) => $part !== ''));
    }

    /**
     * Manufacture one AddressEntity child per schema:address node
     *
     * @param array<string, mixed> $node                 owning JSON-LD node
     * @param array<string, int>   $translationLanguages code => sys_language_uid
     */
    protected function buildAddress(array $node, string $remoteId, string $language, array $translationLanguages = []): void
    {
        $value = $node['schema:address'] ?? null;
        if (!is_array($value) || $value === []) {
            return;
        }

        $addressNodes = array_is_list($value) ? $value : [$value];

        // One geo node describes the record's location, so it can only belong
        // to the first address.
        $geo = $node['schema:geo'] ?? [];
        /** @var array<string, mixed> $geo JSON-LD nodes are string-keyed. */
        $geo = is_array($geo) ? $geo : [];

        $ordinal = 0;
        foreach ($addressNodes as $addressNode) {
            if (!is_array($addressNode) || $addressNode === []) {
                continue;
            }
            /** @var array<string, mixed> $addressNode JSON-LD nodes are string-keyed. */
            $child = new AddressEntity();
            $child->configure($addressNode, $language, $ordinal === 0 ? $geo : [], $remoteId, $ordinal);

            foreach ($translationLanguages as $code => $sysLanguageUid) {
                $child->configureTranslation($addressNode, $code, $sysLanguageUid);
            }

            $this->children[] = $child;
            $ordinal++;
        }
    }

    /**
     * Manufacture one OpeningHourSpecificationEntity child per
     * schema:OpeningHoursSpecification node
     *
     * @param array<string, mixed> $node owning JSON-LD node
     */
    protected function buildOpeningHourSpecifications(array $node, string $remoteId): void
    {
        $this->collectOpeningHourSpecifications(
            $node['schema:openingHoursSpecification'] ?? null,
            $remoteId,
            OpeningHourSpecificationEntity::TYPE_REGULAR
        );
        $this->collectOpeningHourSpecifications(
            $node['schema:specialOpeningHoursSpecification'] ?? null,
            $remoteId,
            OpeningHourSpecificationEntity::TYPE_SPECIAL
        );
    }

    private function collectOpeningHourSpecifications(mixed $value, string $remoteId, string $specificationType): void
    {
        if (!is_array($value)) {
            return;
        }
        $items = array_is_list($value) ? $value : [$value];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            /** @var array<string, mixed> $item JSON-LD nodes are string-keyed. */
            $days = $this->extractDaysOfWeek($item['schema:dayOfWeek'] ?? null);
            foreach ($days === [] ? [''] : $days as $day) {
                $child = new OpeningHourSpecificationEntity();
                $child->configure($remoteId, $specificationType, $day, $item);
                $this->children[] = $child;
            }
        }
    }

    /**
     * schema:dayOfWeek is a single typed @value object or a list of them. Returns
     * the bare day names, e.g. ["Monday"].
     *
     * @return list<string>
     */
    private function extractDaysOfWeek(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $items = array_is_list($value) ? $value : [$value];
        $days = [];
        foreach ($items as $item) {
            $raw = is_array($item) ? (string)($item['@value'] ?? '') : '';
            if ($raw === '') {
                continue;
            }
            $days[] = $this->stripNamespacePrefix($raw);
        }
        return $days;
    }
}
