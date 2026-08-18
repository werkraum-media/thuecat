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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;

/**
 * The values that governed one run, carried as a JSON map in the context
 * column. Never holds the API key: the importer leaves it out when assembling
 * the map, so nothing here has to mask it.
 */
class EffectiveSettings extends ImportLogEntry
{
    protected string $context = '';

    public function getType(): string
    {
        return 'effectiveSettings';
    }

    public function getRemoteId(): string
    {
        return '';
    }

    /**
     * @return array<string, string|int>
     */
    public function getSettings(): array
    {
        $decoded = json_decode($this->context, true);
        if (!is_array($decoded)) {
            return [];
        }

        $settings = [];
        foreach ($decoded as $name => $value) {
            if (is_string($value) || is_int($value)) {
                $settings[(string)$name] = $value;
            }
        }

        return $settings;
    }
}
