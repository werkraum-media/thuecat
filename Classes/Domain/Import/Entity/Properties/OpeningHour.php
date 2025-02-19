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

namespace WerkraumMedia\ThueCat\Domain\Import\Entity\Properties;

use DateTimeImmutable;

class OpeningHour
{
    protected ?DateTimeImmutable $validFrom = null;

    protected ?DateTimeImmutable $validThrough = null;

    protected ?DateTimeImmutable $opens = null;

    protected ?DateTimeImmutable $closes = null;

    /**
     * @var string[]
     */
    protected array $daysOfWeek = [];

    public function isClosingDay(): bool
    {
        return $this->getOpens() === null && $this->getCloses() === null;
    }

    public function getValidFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidThrough(): ?DateTimeImmutable
    {
        return $this->validThrough;
    }

    public function getOpens(): ?DateTimeImmutable
    {
        return $this->opens;
    }

    public function getCloses(): ?DateTimeImmutable
    {
        return $this->closes;
    }

    /**
     * @return string[]
     */
    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setValidFrom(DateTimeImmutable $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setValidThrough(DateTimeImmutable $validThrough): void
    {
        $this->validThrough = $validThrough;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setOpens(DateTimeImmutable $opens): void
    {
        $this->opens = $opens;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setCloses(DateTimeImmutable $closes): void
    {
        $this->closes = $closes;
    }

    /**
     * @param string|array $dayOfWeek
     */
    public function setDayOfWeek($dayOfWeek): void
    {
        if (is_array($dayOfWeek) === false) {
            $dayOfWeek = [$dayOfWeek];
        }

        $this->daysOfWeek = array_map(function (string $dayOfWeek) {
            return mb_substr($dayOfWeek, 7);
        }, $dayOfWeek);
    }
}
