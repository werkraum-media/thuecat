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

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TransientEntity;

// Transient: never registered as `import.entity`, never dispatched by the
// Parser. The parent entity (TouristAttraction) constructs one per
// schema:makesOffer node, collects toArray() outputs and json_encodes the
// list into its own `offers` column.
//
// Output shape matches the legacy frontend models Offer::createFromArray and
// Price::createFromArray so the existing rendering pipeline keeps working:
//   {types:[...], title, description,
//    prices:[{title, description, price, currency, rule}, ...]}
class OfferEntity extends AbstractTransientEntity
{
    /**
     * @var list<string>
     */
    protected array $types = [];
    protected string $title = '';
    protected string $description = '';

    /**
     * @var list<array{title: string, description: string, price: float, currency: string, rule: string}>
     */
    protected array $prices = [];

    public function configure(array $node, string $language): void
    {
        // Offer type lives in thuecat:offerType (typed @value), not in the
        // Offer's @type array. The legacy Offer::getType() picks the first
        // "*Offer" entry, so we emit a single-element list here; the frontend
        // keeps its array-shaped `types` contract without us duplicating the
        // schema:Offer/Thing/Intangible noise from the @type array.
        $this->types = $this->extractTypes($node['thuecat:offerType'] ?? null);
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->description = $this->extractLocalisedValue($node['schema:description'] ?? null, $language);
        $this->prices = $this->extractPrices($node['schema:priceSpecification'] ?? null, $language);
    }

    public function toArray(): array
    {
        return [
            'types' => $this->types,
            'title' => $this->title,
            'description' => $this->description,
            'prices' => $this->prices,
        ];
    }

    /**
     * thuecat:offerType is a single typed @value("thuecat:GuidedTourOffer"),
     * occasionally a list of them. Return the bare member names.
     *
     * @return list<string>
     */
    private function extractTypes(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $types = [];
        foreach ($items as $item) {
            $raw = $this->extractStringValue($item);
            if ($raw === '') {
                continue;
            }
            $types[] = $this->stripNamespacePrefix($raw);
        }

        return $types;
    }

    /**
     * schema:priceSpecification is a single PriceSpecification node or a list
     * of them. Each node becomes one array inside the offer's `prices` list.
     * Kept inline rather than as its own transient class — the shape is small
     * and only used here.
     *
     * @return list<array{title: string, description: string, price: float, currency: string, rule: string}>
     */
    private function extractPrices(mixed $value, string $language): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $prices = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $prices[] = [
                'title' => $this->extractLocalisedValue($item['schema:name'] ?? null, $language),
                'description' => $this->extractLocalisedValue($item['schema:description'] ?? null, $language),
                'price' => (float)$this->extractStringValue($item['schema:price'] ?? null),
                'currency' => $this->stripNamespacePrefix($this->extractStringValue($item['schema:priceCurrency'] ?? null)),
                'rule' => $this->stripNamespacePrefix($this->extractStringValue($item['thuecat:calculationRule'] ?? null)),
            ];
        }

        return $prices;
    }
}
