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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use WerkraumMedia\ThueCat\Typo3\FlexForm\ImportConfigurationDataStructure;

// Serves the import-configuration data structure from PHP (composed once,
// shared fields de-duplicated) instead of the per-type XML files. The type
// comes from the identifier's dataStructureKey, set via columnsOverrides.
#[AsEventListener]
final readonly class ImportConfigurationFlexFormListener
{
    public function __construct(
        private ImportConfigurationDataStructure $dataStructure,
    ) {
    }

    public function __invoke(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (
            ($identifier['tableName'] ?? '') !== 'tx_thuecat_import_configuration'
            || ($identifier['fieldName'] ?? '') !== 'configuration'
        ) {
            return;
        }

        $type = is_string($identifier['dataStructureKey'] ?? null) ? $identifier['dataStructureKey'] : '';
        $dataStructure = $this->dataStructure->forType($type);
        if ($dataStructure === null) {
            return;
        }

        $event->setDataStructure($dataStructure);
    }
}
