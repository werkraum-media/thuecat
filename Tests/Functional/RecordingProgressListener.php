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

    public function progressed(ImportProgress $progress): void
    {
        $this->events[] = $progress;
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
