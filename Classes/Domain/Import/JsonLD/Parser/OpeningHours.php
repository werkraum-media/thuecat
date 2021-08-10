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

namespace WerkraumMedia\ThueCat\Domain\Import\JsonLD\Parser;

class OpeningHours
{
    public function get(array $jsonLD): array
    {
        $openingHours = $jsonLD['schema:openingHoursSpecification'] ?? [];
        if ($openingHours === []) {
            return [];
        }

        if (isset($openingHours['@id'])) {
            return [$this->parseSingleEntry($openingHours)];
        }

        return array_values(array_map([$this, 'parseSingleEntry'], $openingHours));
    }

    private function parseSingleEntry(array $openingHour): array
    {
        return [
            'opens' => $this->getOpens($openingHour),
            'closes' => $this->getCloses($openingHour),
            'from' => $this->getFrom($openingHour),
            'through' => $this->getThrough($openingHour),
            'daysOfWeek' => $this->getDaysOfWeek($openingHour),
        ];
    }

    private function getOpens(array $openingHour): string
    {
        return $openingHour['schema:opens']['@value'] ?? '';
    }

    private function getCloses(array $openingHour): string
    {
        return $openingHour['schema:closes']['@value'] ?? '';
    }

    private function getFrom(array $openingHour): ?\DateTimeImmutable
    {
        if (isset($openingHour['schema:validFrom']['@value'])) {
            return new \DateTimeImmutable($openingHour['schema:validFrom']['@value']);
        }

        return null;
    }

    private function getThrough(array $openingHour): ?\DateTimeImmutable
    {
        if (isset($openingHour['schema:validThrough']['@value'])) {
            return new \DateTimeImmutable($openingHour['schema:validThrough']['@value']);
        }

        return null;
    }

    private function getDaysOfWeek(array $openingHour): array
    {
        if (isset($openingHour['schema:dayOfWeek']['@value'])) {
            return [$this->getDayOfWeekString($openingHour['schema:dayOfWeek']['@value'])];
        }
        $daysOfWeek = array_map(function ($dayOfWeek) {
            return $this->getDayOfWeekString($dayOfWeek['@value']);
        }, $openingHour['schema:dayOfWeek'] ?? []);

        sort($daysOfWeek);
        return $daysOfWeek;
    }

    private function getDayOfWeekString(string $jsonLDValue): string
    {
        return str_replace(
            'schema:',
            '',
            $jsonLDValue
        );
    }
}
