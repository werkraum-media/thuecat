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

namespace WerkraumMedia\ThueCat\Import;

use RuntimeException;
use WerkraumMedia\ThueCat\Import\Settings\ImportTarget;

/**
 * Thrown when an import configuration carries an import target that matches no
 * known one. It decides which sys_category anchors a run resolves, so an
 * unknown value would find no declared setting at any level and switch every
 * kind's mapping off — a run reporting success having imported no categories.
 *
 * An absent or empty value is not unknown: it means the thuecat target.
 */
final class ImportTargetConfigurationException extends RuntimeException
{
    public static function forUnknownTarget(string $configured): self
    {
        return new self(
            'Unknown import target "' . $configured . '". Accepted: '
            . implode(', ', ImportTarget::configuredValues()) . '.',
            1787117122
        );
    }
}
