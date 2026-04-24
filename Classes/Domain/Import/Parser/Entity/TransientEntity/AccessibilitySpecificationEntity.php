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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity;

// Transient: never registered as `import.entity`, never dispatched by the
// Parser. The resolver constructs one per fetched accessibilitySpecification
// node and json_encodes toArray() into the owning entity's
// `accessibility_specification` column.
//
// Output shape matches the legacy Place / AccessibilitySpecification frontend
// model's serialized JSON: flat assoc array with certification fields at top
// (alphabetical), a nested accessibilitySearchCriteria object grouped into
// four fixed facility buckets, then localised short-description fields
// (alphabetical). Absent source fields are omitted rather than emitted as
// empty so the blob stays compact.
class AccessibilitySpecificationEntity extends AbstractTransientEntity
{
    private const CERTIFICATION_KEYS = [
        'certificationAccessibilityDeaf',
        'certificationAccessibilityMental',
        'certificationAccessibilityPartiallyDeaf',
        'certificationAccessibilityPartiallyVisual',
        'certificationAccessibilityVisual',
        'certificationAccessibilityWalking',
        'certificationAccessibilityWheelchair',
    ];

    private const SHORT_DESCRIPTION_KEYS = [
        'shortDescriptionAccessibilityAllGenerations',
        'shortDescriptionAccessibilityAllergic',
        'shortDescriptionAccessibilityDeaf',
        'shortDescriptionAccessibilityMental',
        'shortDescriptionAccessibilityVisual',
        'shortDescriptionAccessibilityWalking',
    ];

    // Fixed group order for search criteria — legacy producer's order. Source
    // iteration emits groups in whatever sequence the JSON-LD lists them in;
    // the output uses this order so the blob is stable across imports.
    private const SEARCH_CRITERIA_GROUPS = [
        'facilityAccessibilityWalking',
        'facilityAccessibilityVisual',
        'facilityAccessibilityDeaf',
        'facilityAccessibilityMental',
    ];

    private string $accessibilityCertificationStatus = '';

    /** @var array<string, string> */
    private array $certifications = [];

    /** @var array<string, list<string>> */
    private array $searchCriteria = [];

    /** @var array<string, string> */
    private array $shortDescriptions = [];

    /**
     * @param array<string, mixed> $node the fetched AccessibilitySpecification node
     */
    public function configure(array $node, string $language): void
    {
        $certification = $node['thuecat:accessibilityCertification'] ?? null;
        if (is_array($certification)) {
            $this->accessibilityCertificationStatus = $this->stripNamespacePrefix(
                $this->extractLanguageValue($certification['thuecat:accessibilityCertificationStatus'] ?? null)
            );
            foreach (self::CERTIFICATION_KEYS as $key) {
                $value = $this->stripNamespacePrefix(
                    $this->extractLanguageValue($certification['thuecat:' . $key] ?? null)
                );
                if ($value !== '') {
                    $this->certifications[$key] = $value;
                }
            }
        }

        $this->searchCriteria = $this->groupSearchCriteria($node['thuecat:accessibilitySearchCriteria'] ?? null);

        foreach (self::SHORT_DESCRIPTION_KEYS as $key) {
            $value = $this->extractLocalisedValue($node['thuecat:' . $key] ?? null, $language);
            if ($value !== '') {
                $this->shortDescriptions[$key] = $value;
            }
        }
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->accessibilityCertificationStatus !== '') {
            $out['accessibilityCertificationStatus'] = $this->accessibilityCertificationStatus;
        }
        foreach ($this->certifications as $key => $value) {
            $out[$key] = $value;
        }
        if ($this->searchCriteria !== []) {
            $out['accessibilitySearchCriteria'] = $this->searchCriteria;
        }
        foreach ($this->shortDescriptions as $key => $value) {
            $out[$key] = $value;
        }
        return $out;
    }

    /**
     * Group each {"@type": "thuecat:facilityAccessibility<X>", "@value":
     * "thuecat:<member>"} entry by its facility bucket. Members within a
     * bucket stay in source order; bucket order is fixed by
     * SEARCH_CRITERIA_GROUPS so the blob is deterministic. Empty buckets
     * are omitted.
     *
     * @return array<string, list<string>>
     */
    private function groupSearchCriteria(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $grouped = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = $this->stripNamespacePrefix(is_string($item['@type'] ?? null) ? $item['@type'] : '');
            $member = $this->stripNamespacePrefix($this->extractLanguageValue($item));
            if ($type === '' || $member === '') {
                continue;
            }
            $grouped[$type][] = $member;
        }

        $ordered = [];
        foreach (self::SEARCH_CRITERIA_GROUPS as $group) {
            if (isset($grouped[$group])) {
                $ordered[$group] = $grouped[$group];
            }
        }
        return $ordered;
    }
}
