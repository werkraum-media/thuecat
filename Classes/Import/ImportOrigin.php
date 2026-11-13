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

namespace WerkraumMedia\ThueCat\Import;

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

/**
 * The import configuration a record is bound to by the run that first writes it. The title is a copy, so it
 * survives a renamed or deleted configuration.
 */
final readonly class ImportOrigin
{
    public function __construct(
        public int $configurationUid,
        public string $configurationTitle,
        public string $syncId,
    ) {
    }

    public static function fromConfiguration(ImportConfiguration $configuration): self
    {
        return new self(
            (int)$configuration->getUid(),
            $configuration->getTitle(),
            match ($configuration->getType()) {
                'syncScope' => $configuration->getSyncScopeId(),
                'syncChannel' => $configuration->getSyncChannelId(),
                default => '',
            }
        );
    }

    /**
     * @return array{origin_configuration_uid: int, origin_configuration_title: string, origin_sync_id: string}
     */
    public function toFields(): array
    {
        return [
            'origin_configuration_uid' => $this->configurationUid,
            'origin_configuration_title' => $this->configurationTitle,
            'origin_sync_id' => $this->syncId,
        ];
    }
}
