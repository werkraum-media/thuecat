<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events;

use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/**
 * Inline child of EventEntity: the venue behind schema:location, written into
 * tx_events_domain_model_location.
 */
class LocationEntity extends AbstractEntity
{
    public const TABLE = 'tx_events_domain_model_location';

    /** Columns feeding ext:events' global_id hash, in its order. */
    protected const HASHED_FIELDS = ['name', 'street', 'zip', 'city', 'district', 'country'];

    protected string $remote_id = '';
    protected string $global_id = '';
    protected string $name = '';
    protected string $street = '';
    protected string $zip = '';
    protected string $city = '';
    protected string $district = '';
    protected string $country = '';
    protected string $phone = '';
    protected string $latitude = '';
    protected string $longitude = '';

    /**
     * Bypasses the parse() path: EventEntity hands over the inline venue node.
     *
     * @param array<string, mixed> $node the schema:location node
     */
    public function configure(array $node, string $language): void
    {
        $address = $node['schema:address'] ?? [];
        /** @var array<string, mixed> $address JSON-LD nodes are string-keyed. */
        $address = is_array($address) ? $address : [];
        $geo = $node['schema:geo'] ?? [];
        /** @var array<string, mixed> $geo JSON-LD nodes are string-keyed. */
        $geo = is_array($geo) ? $geo : [];

        $this->name = $this->extractValue($node['schema:name'] ?? null, $language);
        $this->street = $this->extractValue($address['schema:streetAddress'] ?? null, $language);
        $this->zip = $this->extractValue($address['schema:postalCode'] ?? null, $language);
        $this->city = $this->extractValue($address['schema:addressLocality'] ?? null, $language);
        $this->country = $this->normalizeCountry(
            $this->extractValue($address['schema:addressCountry'] ?? null, $language)
        );
        $this->phone = $this->extractValue($address['schema:telephone'] ?? null, $language);

        $this->extractGeo($geo, $language);

        $this->global_id = $this->generateGlobalId();
        $this->remote_id = $this->global_id;
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

    public function getGlobalId(): string
    {
        return $this->global_id;
    }

    /**
     * Whether the venue carries anything at all
     */
    public function isValid(): bool
    {
        foreach ([...self::HASHED_FIELDS, 'phone'] as $field) {
            if ($this->{$field} !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Reproduces ext:events' Location::generateGlobalId(). Duplicated rather
     * than called because that method is private.
     */
    protected function generateGlobalId(): string
    {
        $values = [];
        foreach (self::HASHED_FIELDS as $field) {
            $values[] = $this->{$field};
        }

        return hash('sha256', implode(',', $values));
    }

    protected function normalizeCountry(string $value): string
    {
        return match ($value) {
            'thuecat:Germany' => 'Deutschland',
            default => $value,
        };
    }

    /**
     * Coordinates are normalized the way ext:events does it, so a row this
     * import writes and a row destination.data writes compare equal.
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

        $this->latitude = $this->normalizeGeocoordinate($latitude);
        $this->longitude = $this->normalizeGeocoordinate($longitude);
    }

    /** Mirrors ext:events' Location::normalizeGeocoordinate(). */
    protected function normalizeGeocoordinate(string $coordinate): string
    {
        if (substr_count($coordinate, ',') === 1 && !str_contains($coordinate, '.')) {
            $coordinate = str_replace(',', '.', $coordinate);
        }

        return number_format((float)$coordinate, 6, '.', '');
    }
}
