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

namespace WerkraumMedia\ThueCat\Import\Progress;

// Keeps console types out of the importer; the command implements the renderer.
interface ImportProgressListener
{
    public function progressed(ImportProgress $progress): void;

    /**
     * The effective settings for the run, handed over before the first fetch.
     * A plain map so the importer needs no renderer type of its own.
     *
     * @param array<string, string|int> $settings setting name => effective value
     */
    public function settingsResolved(array $settings): void;
}
