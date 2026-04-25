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

class TouristInformationEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_tourist_information';
    // Higher than the default 10 — TouristInformation nodes also carry
    // schema:Organization in @type, so without priority the generic
    // OrganisationEntity would win the resolver tie-break.
    protected int $priority = 20;
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    public function parse(array $node, string $language, array $translationLanguages = []): void
    {
        $this->translations = [];
        $this->remote_id = $this->getRemoteId($node);

        $localisedFields = [
            'title' => 'schema:name',
            'description' => 'schema:description',
        ];
        foreach ($localisedFields as $field => $jsonldName) {
            $this->$field = $this->extractLocalisedValue($node[$jsonldName] ?? null, $language);
        }

        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $value = $this->extractLocalisedValue($node[$jsonldName] ?? null, $code);
                $this->recordTranslation($field, $value, $sysLanguageUid);
            }
        }

        // town (tx_thuecat_town) and managed_by (tx_thuecat_organisation) live
        // on the row but stay empty here — the referenced @id stubs only carry
        // ids, not types, so the resolver must look each one up before deciding
        // which table it points to.
        $this->recordTransient('containedInPlace', $node['schema:containedInPlace'] ?? null);
        $this->recordTransient('managedBy', $node['thuecat:managedBy'] ?? null);
    }

    public function handlesTypes(): array
    {
        return ['thuecat:TouristInformation'];
    }
}
