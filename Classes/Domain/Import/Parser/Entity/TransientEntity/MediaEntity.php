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
// Parser. The resolver constructs one per fetched dms_* media node and
// collects toArray() outputs into the owning entity's `media` JSON blob.
//
// Output shape matches the legacy Media frontend model's `data` item shape:
//   {mainImage, type, title, description, url, author, copyrightYear,
//    license: {type, author}}
//
// Author resolution has two sources in JSON-LD:
//   - schema:author as a literal {@value: "Full Name"} → use directly
//   - schema:author as {@id: "…"} pointing at a Person node → resolver must
//     fetch that node and pass the shaped name in via configure()'s
//     $resolvedAuthor argument.
class MediaEntity extends AbstractTransientEntity
{
    private bool $mainImage = false;
    private string $type = 'image';
    private string $title = '';
    private string $description = '';
    private string $url = '';
    private string $author = '';
    private int $copyrightYear = 0;

    /** @var array{type: string, author: string} */
    private array $license = ['type' => '', 'author' => ''];

    /**
     * @param array<string, mixed> $node       the fetched dms_* media node
     * @param string               $kind       'photo' | 'image' | 'video' — drives mainImage + type
     * @param string|null          $resolvedAuthor  pre-shaped author string when schema:author was an @id ref;
     *                                              null when schema:author was a literal string (or absent)
     */
    public function configure(array $node, string $kind, string $language, ?string $resolvedAuthor = null): void
    {
        $this->mainImage = $kind === 'photo';
        $this->type = $kind === 'video' ? 'video' : 'image';

        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->description = $this->extractLocalisedValue($node['schema:description'] ?? null, $language);
        $this->url = $this->extractStringValue($node['schema:url'] ?? null);
        $this->author = $resolvedAuthor ?? $this->extractAuthorString($node['schema:author'] ?? null);
        $this->copyrightYear = (int)$this->extractLanguageValue($node['schema:copyrightYear'] ?? null);
        $this->license = [
            'type' => $this->extractLanguageValue($node['schema:license'] ?? null),
            'author' => $this->extractLocalisedValue($node['thuecat:licenseAuthor'] ?? null, $language),
        ];
    }

    public function toArray(): array
    {
        return [
            'mainImage' => $this->mainImage,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'author' => $this->author,
            'copyrightYear' => $this->copyrightYear,
            'license' => $this->license,
        ];
    }

    /**
     * schema:author can be a literal string typed @value. When it's an @id
     * ref instead, the resolver has pre-fetched the Person node and passes
     * the shaped name in through configure(); this helper only handles the
     * literal-string case.
     */
    private function extractAuthorString(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }

        if (isset($value['@id'])) {
            return '';
        }

        return $this->extractStringValue($value);
    }

    /**
     * Return the author @id when schema:author is a ref, null otherwise.
     * Call before configure() so the resolver knows whether to fetch a Person
     * node and shape a name.
     *
     * @param array<string, mixed> $node
     */
    public static function authorReference(array $node): ?string
    {
        $author = $node['schema:author'] ?? null;
        if (is_array($author) && isset($author['@id']) && is_scalar($author['@id'])) {
            return (string)$author['@id'];
        }
        return null;
    }

    /**
     * Shape a Person node into "givenName familyName" (German takes language
     * precedence via $language); fall back to schema:name; then to ''.
     *
     * @param array<string, mixed> $personNode
     */
    public static function shapePersonName(array $personNode, string $language): string
    {
        $given = self::pickLocalised($personNode['schema:givenName'] ?? null, $language);
        $family = self::pickLocalised($personNode['schema:familyName'] ?? null, $language);

        $parts = array_filter([$given, $family], static fn (string $part) => $part !== '');
        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return self::pickLocalised($personNode['schema:name'] ?? null, $language);
    }

    private static function pickLocalised(mixed $value, string $language): string
    {
        if (!is_array($value)) {
            return '';
        }
        if (array_is_list($value)) {
            foreach ($value as $item) {
                if (
                    is_array($item)
                    && ($item['@language'] ?? null) === $language
                    && isset($item['@value'])
                    && is_scalar($item['@value'])
                ) {
                    return (string)$item['@value'];
                }
            }
            return '';
        }
        if (
            ($value['@language'] ?? null) === $language
            && isset($value['@value'])
            && is_scalar($value['@value'])
        ) {
            return (string)$value['@value'];
        }
        return '';
    }
}
