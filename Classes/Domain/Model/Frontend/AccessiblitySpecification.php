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

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Core\Type\TypeInterface;

class AccessiblitySpecification implements TypeInterface
{
    /**
     * @var mixed[]
     */
    private array $data;

    public function __construct(
        private readonly string $serialized
    ) {
        $this->data = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getCertificationStatus(): string
    {
        return $this->data['accessibilityCertificationStatus'] ?? '';
    }

    public function getSearchCriteria(): array
    {
        return $this->data['accessibilitySearchCriteria'] ?? [];
    }

    public function getCertificationDeaf(): string
    {
        return $this->data['certificationAccessibilityDeaf'] ?? '';
    }

    public function getCertificationMental(): string
    {
        return $this->data['certificationAccessibilityMental'] ?? '';
    }

    public function getCertificationPartiallyDeaf(): string
    {
        return $this->data['certificationAccessibilityPartiallyDeaf'] ?? '';
    }

    public function getCertificationPartiallyVisual(): string
    {
        return $this->data['certificationAccessibilityPartiallyVisual'] ?? '';
    }

    public function getCertificationVisual(): string
    {
        return $this->data['certificationAccessibilityVisual'] ?? '';
    }

    public function getCertificationWalking(): string
    {
        return $this->data['certificationAccessibilityWalking'] ?? '';
    }

    public function getCertificationWheelchair(): string
    {
        return $this->data['certificationAccessibilityWheelchair'] ?? '';
    }

    public function getShortDescriptionAllGenerations(): string
    {
        return $this->data['shortDescriptionAccessibilityAllGenerations'] ?? '';
    }

    public function getShortDescriptionAllergic(): string
    {
        return $this->data['shortDescriptionAccessibilityAllergic'] ?? '';
    }

    public function getShortDescriptionDeaf(): string
    {
        return $this->data['shortDescriptionAccessibilityDeaf'] ?? '';
    }

    public function getShortDescriptionMental(): string
    {
        return $this->data['shortDescriptionAccessibilityMental'] ?? '';
    }

    public function getShortDescriptionVisual(): string
    {
        return $this->data['shortDescriptionAccessibilityVisual'] ?? '';
    }

    public function getShortDescriptionWalking(): string
    {
        return $this->data['shortDescriptionAccessibilityWalking'] ?? '';
    }

    public function __toString(): string
    {
        return $this->serialized;
    }
}
