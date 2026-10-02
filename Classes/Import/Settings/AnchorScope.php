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

namespace WerkraumMedia\ThueCat\Import\Settings;

/**
 * The name a record kind's sys_category anchors are read under, as declared by
 * a top-level entity's anchorScope(). Used verbatim as the settings segment, so
 * the names integrators already configured stay valid. Kinds reached only as
 * relations use the default scope.
 */
final class AnchorScope
{
    public const DEFAULT = 'thuecat';

    /**
     * @param non-empty-string $value
     */
    public function __construct(
        public readonly string $value
    ) {
    }

    public static function default(): self
    {
        return new self(self::DEFAULT);
    }
}
