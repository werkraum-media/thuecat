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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity;

use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\PropertyValues;

class AccessibilitySpecification implements MapsToType
{
    /**
     * @var AccessibilityCertification
     */
    protected $accessibilityCertification;

    /**
     * @var array
     */
    protected $accessibilitySearchCriteria = [];

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityAllGenerations = '';

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityAllergic = '';

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityDeaf = '';

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityMental = '';

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityVisual = '';

    /**
     * @var string
     */
    protected $shortDescriptionAccessibilityWalking = '';

    public function getAccessibilityCertification(): ?AccessibilityCertification
    {
        return $this->accessibilityCertification;
    }

    public function getAccessibilitySearchCriteria(): array
    {
        return $this->accessibilitySearchCriteria;
    }

    public function getShortDescriptionAccessibilityAllGenerations(): string
    {
        return $this->shortDescriptionAccessibilityAllGenerations;
    }

    public function getShortDescriptionAccessibilityAllergic(): string
    {
        return $this->shortDescriptionAccessibilityAllergic;
    }

    public function getShortDescriptionAccessibilityDeaf(): string
    {
        return $this->shortDescriptionAccessibilityDeaf;
    }

    public function getShortDescriptionAccessibilityMental(): string
    {
        return $this->shortDescriptionAccessibilityMental;
    }

    public function getShortDescriptionAccessibilityVisual(): string
    {
        return $this->shortDescriptionAccessibilityVisual;
    }

    public function getShortDescriptionAccessibilityWalking(): string
    {
        return $this->shortDescriptionAccessibilityWalking;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAccessibilitySearchCriteria(array $accessibilitySearchCriteria): void
    {
        foreach ($accessibilitySearchCriteria as $criteria) {
            $criteria = PropertyValues::removePrefixFromEntries($criteria);
            $this->accessibilitySearchCriteria[$criteria['type']][] = $criteria['value'];
        }
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAccessibilityCertification(AccessibilityCertification $accessibilityCertification): void
    {
        $this->accessibilityCertification = $accessibilityCertification;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityAllGenerations(string $shortDescriptionAccessibilityAllGenerations): void
    {
        $this->shortDescriptionAccessibilityAllGenerations = $shortDescriptionAccessibilityAllGenerations;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityAllergic(string $shortDescriptionAccessibilityAllergic): void
    {
        $this->shortDescriptionAccessibilityAllergic = $shortDescriptionAccessibilityAllergic;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityDeaf(string $shortDescriptionAccessibilityDeaf): void
    {
        $this->shortDescriptionAccessibilityDeaf = $shortDescriptionAccessibilityDeaf;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityMental(string $shortDescriptionAccessibilityMental): void
    {
        $this->shortDescriptionAccessibilityMental = $shortDescriptionAccessibilityMental;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityVisual(string $shortDescriptionAccessibilityVisual): void
    {
        $this->shortDescriptionAccessibilityVisual = $shortDescriptionAccessibilityVisual;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setShortDescriptionAccessibilityWalking(string $shortDescriptionAccessibilityWalking): void
    {
        $this->shortDescriptionAccessibilityWalking = $shortDescriptionAccessibilityWalking;
    }

    public static function getSupportedTypes(): array
    {
        return [
            'thuecat:AccessibilitySpecification',
        ];
    }

    public static function getPriority(): int
    {
        return 10;
    }
}
