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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

abstract class AbstractEntity implements EntityInterface
{
    protected int $priority = 10;

    public function getRemoteId(array $node): string
    {
        return (string)$node['@id'];
    }

    protected function prefixRelation(string $remoteId): string
    {
        return 'REF:' . $remoteId;
    }


    protected function extractStringValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string)($value['@value'] ?? '');
        }

        return '';
    }

    protected function extractLanguageValue(mixed $value): string
    {
        if (is_array($value) && isset($value['@value'])) {
            return (string)$value['@value'];
        }

        return '';
    }

    public function toArray(): array
    {
        $array = get_object_vars($this);
        unset($array['table']);

        return array_filter($array);
    }

    public function getPriority():int
    {
        return $this->priority;
    }

    abstract public function handlesTypes(): array;
}