<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use WerkraumMedia\ThueCat\Extension;

// TODO: typo3/cms-core:15 Remove this listener once we drop TYPO3 v13.
// This is only kept for compatibility with v13 and breaking change: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-107047-RemovePointerFieldFunctionalityOfTCAFlex.html
#[AsEventListener]
final readonly class AfterTcaCompilationEventListener
{
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '14.0', '>=')) {
            return;
        }

        $flexFormConfigurationPath = 'FILE:EXT:' . Extension::EXTENSION_KEY . '/Configuration/FlexForm/';

        $tca = $event->getTca();
        $tca['tx_thuecat_import_configuration']['columns']['configuration']['config'] = array_merge($tca['tx_thuecat_import_configuration']['columns']['configuration']['config'], [
            'ds_pointerField' => 'type',
            'ds' => [
                'default' => $flexFormConfigurationPath . 'ImportConfiguration/Static.xml',
                'static' => $flexFormConfigurationPath . 'ImportConfiguration/Static.xml',
                'syncScope' => $flexFormConfigurationPath . 'ImportConfiguration/SyncScope.xml',
                'containsPlace' => $flexFormConfigurationPath . 'ImportConfiguration/ContainsPlace.xml',
            ],
        ]);

        foreach ($tca['tx_thuecat_import_configuration']['types'] as $type => $configuration) {
            if (isset($configuration['columnsOverrides']['pi_flexform']['configuration']['ds'])) {
                unset($tca['tx_thuecat_import_configuration']['types'][$type]['columnsOverrides']['pi_flexform']['configuration']['ds']);
            }
        }

        $event->setTca($tca);
    }
}
