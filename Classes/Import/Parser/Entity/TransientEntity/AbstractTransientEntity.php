<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Support\LocalisedValueReader;

// Shared base for nested JSON-LD shapes whose rendered form is a JSON blob on
// a parent entity's column (Address, OpeningHours, …). Transients are not
// registered as `import.entity` services and not dispatched by the Parser —
// the parent owns construction, configuration, and json_encoding.
//
// Kept deliberately separate from Entity\AbstractEntity: top-level entities
// carry transients, priorities, handlesTypes(), and the DataHandler payload
// contract; transients have none of that. Only the shared value-extraction
// helpers live here.
abstract class AbstractTransientEntity
{
    abstract public function toArray(): array;

    /**
     * The one text extraction in the import layer; see LocalisedValueReader.
     * Mirrors AbstractEntity::extractValue — the two roots share no base, so
     * each exposes the collaborator to its own subclasses.
     */
    protected function extractValue(mixed $value, string $language): string
    {
        return (new LocalisedValueReader())->read($value, $language);
    }

    /**
     * Drop the `thuecat:` / `schema:` prefix from an enum URI so the stored
     * value matches the bare member name used by the frontend models.
     */
    protected function stripNamespacePrefix(string $value): string
    {
        $colon = strpos($value, ':');
        return $colon === false ? $value : substr($value, $colon + 1);
    }
}
