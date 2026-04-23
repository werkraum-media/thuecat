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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

// Entities are constructed via the ServiceLocator, which cannot supply the node.
// Data extraction therefore happens post-construction through configure().
#[AutoconfigureTag('import.entity')]
interface EntityInterface
{
    public function configure(array $node, ParserContext $context): void;

    public function getRemoteId(array $node): string;

    /** @return array<string, string|int|float> */
    public function toArray(): array;

    /**
     * Unresolved references captured during configure() for the resolver to
     * swap into real relation fields post-parse. Keyed by JSON-LD field name
     * with the schema:/thuecat: prefix stripped.
     *
     * @return array<string, list<string>>
     */
    public function getTransients(): array;

    public function handlesTypes(): array;

    public function getPriority(): int;
}
