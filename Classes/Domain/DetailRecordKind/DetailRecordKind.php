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

namespace WerkraumMedia\ThueCat\Domain\DetailRecordKind;

/**
 * A record kind with its own detail page: the table, the Extbase show plugin
 * that renders one record, and the site setting naming its canonical show page.
 */
final readonly class DetailRecordKind
{
    /**
     * @param string $identifier      stable key used in TypoScript and link formats, e.g. `attraction`
     * @param string $showPageSetting site settings key, e.g. `thuecat.pois.pid_show`
     */
    public function __construct(
        public string $identifier,
        public string $table,
        public string $extensionName,
        public string $pluginName,
        public string $argumentName,
        public string $showPageSetting,
    ) {
    }
}
