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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

// Entities are constructed via the ServiceLocator, which cannot supply the node.
// Data extraction therefore happens post-construction through configure().
#[AutoconfigureTag('import.entity')]
interface EntityInterface
{
    /**
     * @param array<string, int> $translationLanguages Two-letter language
     *        code → target sys_language_uid. The default-language row goes
     *        into the entity's data; for each entry here, fields whose
     *        JSON-LD source carries a matching `@language` tag are recorded
     *        into the translations bucket via recordTranslation().
     */
    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages): void;

    public function getRemoteId(array $node): string;

    /** @return array<string, string|int|float> */
    public function toArray(): array;

    /**
     * Unresolved references captured during configure() for the resolver to
     * swap into real relation fields post-parse. Keyed by JSON-LD field name
     * with the schema:/thuecat: prefix stripped.
     *
     * Most buckets carry list<string> (ref→uid lookups); the `media` bucket
     * carries list<array{kind, id}> so the resolver can distinguish
     * schema:photo vs schema:image vs schema:video origin on the shaped
     * output.
     *
     * @return array<string, list<string>|list<array{kind: string, id: string}>>
     */
    public function getTransients(): array;

    /**
     * Translated scalar values gathered during parse(), keyed by
     * sys_language_uid → field → translated string. Only fields whose
     * JSON-LD source actually carries a matching `@language` entry are
     * recorded; empty extractions are dropped.
     *
     * @return array<int, array<string, string>>
     */
    public function getTranslations(): array;

    public function handlesTypes(): array;

    public function getPriority(): int;

    /**
     * Side entities the parent manufactured during parse() — typically inline
     * 1:n children (e.g. Date rows for an Event) that don't have their own
     * JSON-LD nodes in the @graph but still need to flow through the same
     * Resolver/DataHandler pipeline as top-level entities. The Parser flushes
     * each child into the DataHandlerPayload right after the parent.
     *
     * Default: no children. Override in entities that synthesize side rows.
     *
     * @return list<EntityInterface>
     */
    public function getChildren(): array;

    /**
     * Categories the parent derived during parse(), for the resolver to wire.
     * field is the destination relation column; remoteId is prefixed by source
     * field to avoid collisions; title seeds a new category. Default: none.
     *
     * @return list<array{field: string, remoteId: string, title: string}>
     */
    public function getCategories(): array;

    /**
     * Match reports for the import report, one per field ('kind'). Default: none.
     *
     * @return list<array{kind: string, sourcePrefix: string, matched: array<string, string>, unmatched: list<string>}>
     */
    public function getMatchReports(): array;
}
