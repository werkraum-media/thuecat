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

class AccessibilityCertification implements MapsToType
{
    /**
     * @var string
     */
    protected $accessibilityCertificationStatus = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityDeaf = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityMental = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityPartiallyDeaf = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityPartiallyVisual = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityVisual = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityWalking = '';

    /**
     * @var string
     */
    protected $certificationAccessibilityWheelchair = '';

    public function getAccessibilityCertificationStatus(): string
    {
        return $this->accessibilityCertificationStatus;
    }

    public function getCertificationAccessibilityDeaf(): string
    {
        return $this->certificationAccessibilityDeaf;
    }

    public function getCertificationAccessibilityMental(): string
    {
        return $this->certificationAccessibilityMental;
    }

    public function getCertificationAccessibilityPartiallyDeaf(): string
    {
        return $this->certificationAccessibilityPartiallyDeaf;
    }

    public function getCertificationAccessibilityPartiallyVisual(): string
    {
        return $this->certificationAccessibilityPartiallyVisual;
    }

    public function getCertificationAccessibilityVisual(): string
    {
        return $this->certificationAccessibilityVisual;
    }

    public function getCertificationAccessibilityWalking(): string
    {
        return $this->certificationAccessibilityWalking;
    }

    public function getCertificationAccessibilityWheelchair(): string
    {
        return $this->certificationAccessibilityWheelchair;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setAccessibilityCertificationStatus(string $accessibilityCertificationStatus): void
    {
        $this->accessibilityCertificationStatus = PropertyValues::removePrefixFromEntry($accessibilityCertificationStatus);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityDeaf(string $certificationAccessibilityDeaf): void
    {
        $this->certificationAccessibilityDeaf = PropertyValues::removePrefixFromEntry($certificationAccessibilityDeaf);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityMental(string $certificationAccessibilityMental): void
    {
        $this->certificationAccessibilityMental = PropertyValues::removePrefixFromEntry($certificationAccessibilityMental);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityPartiallyDeaf(string $certificationAccessibilityPartiallyDeaf): void
    {
        $this->certificationAccessibilityPartiallyDeaf = PropertyValues::removePrefixFromEntry($certificationAccessibilityPartiallyDeaf);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityPartiallyVisual(string $certificationAccessibilityPartiallyVisual): void
    {
        $this->certificationAccessibilityPartiallyVisual = PropertyValues::removePrefixFromEntry($certificationAccessibilityPartiallyVisual);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityVisual(string $certificationAccessibilityVisual): void
    {
        $this->certificationAccessibilityVisual = PropertyValues::removePrefixFromEntry($certificationAccessibilityVisual);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityWalking(string $certificationAccessibilityWalking): void
    {
        $this->certificationAccessibilityWalking = PropertyValues::removePrefixFromEntry($certificationAccessibilityWalking);
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCertificationAccessibilityWheelchair(string $certificationAccessibilityWheelchair): void
    {
        $this->certificationAccessibilityWheelchair = PropertyValues::removePrefixFromEntry($certificationAccessibilityWheelchair);
    }

    public static function getSupportedTypes(): array
    {
        return [
            'thuecat:AccessibilityCertification',
        ];
    }

    public static function getPriority(): int
    {
        return 10;
    }
}
