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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use WerkraumMedia\ThueCat\Import\Progress\ImportPhase;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgress;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;

final class RecordingProgressListener implements ImportProgressListener
{
    /**
     * @var list<ImportProgress>
     */
    public array $events = [];

    /**
     * @var array<string, string|int>
     */
    public array $settings = [];

    public function progressed(ImportProgress $progress): void
    {
        $this->events[] = $progress;
    }

    /**
     * @param array<string, string|int> $settings
     */
    public function settingsResolved(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return list<ImportProgress>
     */
    public function ofPhase(ImportPhase $phase): array
    {
        return array_values(array_filter(
            $this->events,
            static fn (ImportProgress $event): bool => $event->phase === $phase
        ));
    }

    /**
     * @return list<string>
     */
    public function phaseOrder(): array
    {
        $order = [];
        foreach ($this->events as $event) {
            if ($order === [] || end($order) !== $event->phase->value) {
                $order[] = $event->phase->value;
            }
        }

        return $order;
    }
}
