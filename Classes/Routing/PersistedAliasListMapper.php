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

namespace WerkraumMedia\ThueCat\Routing;

use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;

/**
 * Maps a comma-separated list of uids to their slugs, joined by `+`, and back.
 *
 * Each value goes through core's alias mapping, language overlay and site
 * binding included. Slugs are generated sorted, so a selection has one URL
 * whatever its order. A list with any value that cannot be mapped is not
 * mapped at all.
 */
class PersistedAliasListMapper extends PersistedAliasMapper
{
    protected const SLUG_DELIMITER = '+';

    protected const UID_DELIMITER = ',';

    public function generate(string $value): ?string
    {
        $slugs = [];
        foreach (explode(self::UID_DELIMITER, $value) as $uid) {
            $slug = parent::generate($uid);
            if ($slug === null) {
                return null;
            }
            $slugs[] = $slug;
        }
        sort($slugs);

        return implode(self::SLUG_DELIMITER, $slugs);
    }

    public function resolve(string $value): ?string
    {
        $uids = [];
        foreach (explode(self::SLUG_DELIMITER, $value) as $slug) {
            $uid = parent::resolve($slug);
            if ($uid === null) {
                return null;
            }
            $uids[] = $uid;
        }

        return implode(self::UID_DELIMITER, $uids);
    }
}
