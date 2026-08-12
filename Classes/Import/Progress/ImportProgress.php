<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Progress;

final class ImportProgress
{
    /**
     * @param int|null $total null where the size is not knowable in advance;
     *        renderers must show liveness rather than invent a percentage
     */
    public function __construct(
        public readonly ImportPhase $phase,
        public readonly string $label = '',
        public readonly ?int $current = null,
        public readonly ?int $total = null,
    ) {
    }

    public function hasPosition(): bool
    {
        return $this->current !== null && $this->total !== null && $this->total > 0;
    }
}
