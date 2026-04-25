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

// Organisation's "manages_*" / "manages_towns" fields are reverse inline relations
// in TCA (foreign_field on the child table). The child records write managed_by;
// Organisation itself has no outgoing relation data to persist.
class OrganisationEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_organisation';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    public function parse(array $node, string $language, array $translationLanguages = []): void
    {
        $this->translations = [];
        $this->remote_id = $this->getRemoteId($node);

        // Field-name → JSON-LD key map; drives both the default-language
        // assignment and the translation probe so the two stay in sync.
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
    }

    public function handlesTypes(): array
    {
        return [
            'schema:Organization',
        ];
    }
}
