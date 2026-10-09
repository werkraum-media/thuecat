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

namespace WerkraumMedia\ThueCat\Service\FilterField;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Declares where one filter field's values are read from.
 *
 * A field states its own value shape only. Which records those values must be
 * carried by is the scope's decision, so one field serves every record kind
 * offering it.
 *
 * Implementations are container-shared and therefore carry table and column
 * names only. A resolved anchor or storage page belongs to a single request and
 * must not be held here.
 */
#[AutoconfigureTag('search.filter.field')]
interface FilterFieldDefinition
{
    /** Demand property and view key; never the MM field name. */
    public function getName(): string;

    public function getOptionTable(): string;
}
