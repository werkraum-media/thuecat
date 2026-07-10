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

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use WerkraumMedia\ThueCat\Typo3\FlexForm\ImportConfigurationDataStructure;

// Serves the import-configuration data structure from PHP (composed once,
// shared fields de-duplicated) instead of the per-type XML files. Fires on
// both v13 and v14; the type comes from the identifier's dataStructureKey
// (v13 via ds_pointerField=type, v14 via columnsOverrides).
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
        // TODO: typo3/cms-core:15 Drop 'pi_flexform' once v13 support goes; v14
        // names the field 'configuration'.
        $fieldName = is_string($identifier['fieldName'] ?? null) ? $identifier['fieldName'] : '';
        if (
            ($identifier['tableName'] ?? '') !== 'tx_thuecat_import_configuration'
            || !in_array($fieldName, ['configuration', 'pi_flexform'], true)
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
