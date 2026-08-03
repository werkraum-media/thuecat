<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
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

namespace WerkraumMedia\ThueCat\Frontend\Cache;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Cache tags derived from the records an entry was built from.
 */
class CacheTagCollector
{
    public function __construct(
        protected readonly DataMapper $dataMapper,
    ) {
    }

    /**
     * `<table>_<uid>` per record, for entries rendering specific records.
     *
     * An empty result yields no tag and would never be invalidated, so it falls
     * back to the bare `<table>`. The caller names it because no record can.
     *
     * @param iterable<mixed> $records
     *
     * @return list<string>
     */
    public function forRecords(iterable $records, string $emptyResultTable = ''): array
    {
        $tags = [];
        foreach ($records as $record) {
            if (!$record instanceof DomainObjectInterface) {
                continue;
            }
            $tags[$this->tableFor($record) . '_' . $record->getUid()] = true;
        }

        if ($tags === [] && $emptyResultTable !== '') {
            return [$emptyResultTable];
        }

        return array_keys($tags);
    }

    /**
     * `<table>` per distinct type, for entries depending on a whole set: an
     * added or removed record changes what they show, which no per-uid tag
     * covers.
     *
     * A set may legitimately be empty — untranslated options offer nothing — so
     * the caller names the tables rather than leaving such an entry untagged.
     *
     * @param list<string> $tables Tables the sets draw from, empty or not.
     * @param iterable<mixed> ...$recordSets
     *
     * @return list<string>
     */
    public function forRecordSets(array $tables, iterable ...$recordSets): array
    {
        $tags = [];
        foreach ($tables as $table) {
            $tags[$table] = true;
        }
        foreach ($recordSets as $records) {
            foreach ($records as $record) {
                if (!$record instanceof DomainObjectInterface) {
                    continue;
                }
                $tags[$this->tableFor($record)] = true;
            }
        }

        return array_keys($tags);
    }

    /**
     * @param class-string<DomainObjectInterface> $model
     */
    public function tableForModel(string $model): string
    {
        return $this->dataMapper->getDataMap($model)->getTableName();
    }

    protected function tableFor(DomainObjectInterface $record): string
    {
        return $this->tableForModel($record::class);
    }
}
