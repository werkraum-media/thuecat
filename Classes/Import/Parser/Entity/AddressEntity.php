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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\CurieExpander;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

// Inline child of a Place entity: one row per schema:address node, one per
// language.
//
// remote_id pattern: <parentRemoteId>::addr::<ordinal>. The source node's own
// @id is a blank node — a serialisation artefact, not an upstream key — so it
// is not stable across imports and cannot match a child to its existing row.
// The ordinal is the address's position in the source, since schema:address
// may be a list. The separator also keeps the child's id distinct from its
// parent's, which matters because translation status is keyed by remote_id
// without the table.
class AddressEntity extends AbstractEntity
{
    public const TABLE = 'tx_thuecat_address';

    public const SEPARATOR = '::addr::';

    protected string $remote_id = '';
    protected string $street = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $region = '';
    protected string $country = '';
    protected string $email = '';
    protected string $phone = '';
    protected string $fax = '';
    protected string $latitude = '';
    protected string $longitude = '';

    /**
     * Bypasses the parse() path: the parent hands over the already-extracted
     * nodes. Address and geo are one record here but two sibling JSON-LD keys.
     *
     * @param array<string, mixed> $node     a single schema:address node
     * @param array<string, mixed> $geo_node the sibling schema:geo node
     */
    public function configure(array $node, string $language, array $geo_node = [], string $parentRemoteId = '', int $ordinal = 0): void
    {
        $this->remote_id = $parentRemoteId . self::SEPARATOR . $ordinal;
        $this->street = $this->extractValue($node['schema:streetAddress'] ?? null, $language);
        $this->zip = $this->extractValue($node['schema:postalCode'] ?? null, $language);
        $this->city = $this->extractValue($node['schema:addressLocality'] ?? null, $language);
        $this->region = $this->extractValue($node['schema:addressRegion'] ?? null, $language);
        $this->country = $this->extractValue($node['schema:addressCountry'] ?? null, $language);
        $this->email = $this->extractValue($node['schema:email'] ?? null, $language);
        $this->phone = $this->extractValue($node['schema:telephone'] ?? null, $language);
        $this->fax = $this->extractValue($node['schema:faxNumber'] ?? null, $language);
        if ($geo_node !== []) {
            $this->extractGeo($geo_node, $language);
        }
    }

    /**
     * Text values only: coordinates are the same place in every language.
     *
     * @param array<string, mixed> $node
     */
    public function configureTranslation(array $node, string $language, int $sysLanguageUid): void
    {
        $fields = [
            'street' => 'schema:streetAddress',
            'zip' => 'schema:postalCode',
            'city' => 'schema:addressLocality',
            'region' => 'schema:addressRegion',
            'country' => 'schema:addressCountry',
            'email' => 'schema:email',
            'phone' => 'schema:telephone',
            'fax' => 'schema:faxNumber',
        ];

        foreach ($fields as $field => $jsonldName) {
            $source = $node[$jsonldName] ?? null;
            $value = $this->extractValue($source, $language);
            // An untagged literal reads the same in every language, so recording
            // it would claim a translation the source never delivered. An
            // untagged CURIE is a reference: the resolver reads its label per
            // language, so it travels on and only falls back to the bare member
            // name when the vocabulary cannot answer.
            if (!$this->carriesLanguage($source, $language) && !$this->isCurie($value)) {
                continue;
            }

            $this->recordTranslation($field, $value, $sysLanguageUid);
        }
    }

    /**
     * Whether the source states this value is in $language, rather than leaving
     * it untagged.
     */
    protected function carriesLanguage(mixed $value, string $language): bool
    {
        if (!is_array($value) || $value === []) {
            return false;
        }

        if (!array_is_list($value)) {
            return ($value['@language'] ?? null) === $language;
        }

        foreach ($value as $item) {
            if (is_array($item) && ($item['@language'] ?? null) === $language) {
                return true;
            }
        }

        return false;
    }

    /** A namespaced member name the resolver can look a label up for. */
    protected function isCurie(string $value): bool
    {
        return (new CurieExpander())->expand($value) !== null;
    }

    /** No-op: manufactured by the parent, never dispatched from a node. */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
    }

    /** Empty so the Parser's @type dispatch never picks this up. */
    public function handlesTypes(): array
    {
        return [];
    }

    /**
     * Kept as strings so the value reaches the column exactly as delivered.
     * A partial pair is dropped — half a coordinate places nothing.
     *
     * @param array<string, mixed> $geoNode
     */
    protected function extractGeo(array $geoNode, string $language): void
    {
        $latitude = $this->extractValue($geoNode['schema:latitude'] ?? null, $language);
        $longitude = $this->extractValue($geoNode['schema:longitude'] ?? null, $language);

        if ($latitude === '' || $longitude === '') {
            return;
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
}
