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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Service\DateBasedFilter;

/**
 * @implements \Iterator<int, OpeningHour>
 */
class OpeningHours implements TypeInterface, \Iterator, \Countable
{
    /**
     * @var string
     */
    private $serialized = '';

    /**
     * @var mixed[]
     */
    private $array = [];

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(string $serialized)
    {
        $this->serialized = $serialized;
        $this->array = $this->createArray($serialized);
    }

    private function createArray(string $serialized): array
    {
        $array = array_map(
            [OpeningHour::class, 'createFromArray'],
            json_decode($serialized, true) ?? []
        );

        $array = GeneralUtility::makeInstance(DateBasedFilter::class)
            ->filterOutPreviousDates(
                $array,
                function (OpeningHour $hour): ?\DateTimeImmutable {
                    return $hour->getThrough();
                }
            );

        usort($array, function (OpeningHour $hourA, OpeningHour $hourB) {
            return $hourA->getFrom() <=> $hourB->getFrom();
        });

        return array_values($array);
    }

    public function __toString(): string
    {
        return $this->serialized;
    }

    public function current(): OpeningHour
    {
        return $this->array[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->array);
    }
}
