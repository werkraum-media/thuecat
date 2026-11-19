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

namespace WerkraumMedia\ThueCat\Removal;

/**
 * Describes how far a removal job got, as the module lists it.
 */
final readonly class RemovalJobState
{
    /**
     * @param bool $stalled queued or running without progress for longer than the consumer should take
     */
    public function __construct(
        public RemovalJob $job,
        public string $status,
        public int $removedRoots,
        public int $skippedRoots,
        public int $removedRelations,
        public int $removedFiles,
        public int $errors,
        public string $errorLog,
        public bool $stalled,
    ) {
    }
}
