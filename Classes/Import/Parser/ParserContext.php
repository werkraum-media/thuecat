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

namespace WerkraumMedia\ThueCat\Import\Parser;

class ParserContext
{
    /**
     * Parsing has no logger; collected here and flushed after the run.
     *
     * @var array<string, list<string>> event remote_id => day values that could not seed a series
     */
    public array $unusableScheduleDays = [];

    /**
     * @var array<string, list<string>> event remote_id => usable weekdays the import could not carry
     */
    public array $droppedScheduleDays = [];

    /**
     * @var array<string, string> event remote_id => title, for events that ended up with no date at all
     */
    public array $eventsWithoutDates = [];

    /**
     * @var array<string, string> event remote_id => title, for events publishing event-level date keys that could not be resolved
     */
    public array $unresolvableEventDates = [];

    public function __construct(
        public readonly int $importConfigurationUid,
        public readonly string $apiDomain = '',
    ) {
    }
}
