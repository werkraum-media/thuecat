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
 * The kind of records an import writes, taken from the import configuration's
 * importTarget flexform field. One importer serves every target; the target
 * decides which sys_category anchors a run resolves, so each target keeps its
 * own category tree within a site.
 *
 * The backed values are the flexform's, which are extension keys.
 */
enum ImportTarget: string
{
    case Thuecat = 'thuecat';
    case Events = 'events';

    /**
     * Null means "configured, but no such target" — deliberately not an
     * exception and deliberately not a default. The validator turns it into a
     * pre-flight failure naming the value; defaulting here would resolve
     * anchors nobody declared and switch every kind's mapping off silently.
     *
     * An empty value is not unknown: configurations predating the field import
     * ThueCat POI structures by definition.
     */
    public static function tryFromConfigured(string $value): ?self
    {
        if ($value === '') {
            return self::Thuecat;
        }

        return self::tryFrom($value);
    }

    /**
     * For error messages, so a rejection can state what it accepts.
     *
     * @return list<string>
     */
    public static function configuredValues(): array
    {
        return array_map(
            static fn (self $target): string => $target->value,
            self::cases()
        );
    }
}
