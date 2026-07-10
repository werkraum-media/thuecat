<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Typo3\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

// TODO: typo3/cms-core:15 Remove this whole listener once we drop TYPO3 v13.
// v13 needs ds_pointerField to derive the dataStructureKey from the record's
// type; v14 does that via columnsOverrides. The DS content itself comes from
// ImportConfigurationFlexFormListener on both versions.
// Breaking change: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Breaking-107047-RemovePointerFieldFunctionalityOfTCAFlex.html
#[AsEventListener]
final readonly class AfterTcaCompilationEventListener
{
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '14.0', '>=')) {
            return;
        }

        // Placeholder structures; only the array keys matter (they resolve the
        // dataStructureKey to the record type). Must be NON-empty or identifier
        // resolution rejects it. Real content is set at parse time.
        $placeholder = '<T3DataStructure><sheets><sDEF><ROOT><type>array</type><el><placeholder><config><type>passthrough</type></config></placeholder></el></ROOT></sDEF></sheets></T3DataStructure>';

        $tca = $event->getTca();
        $tca['tx_thuecat_import_configuration']['columns']['configuration']['config'] = array_merge($tca['tx_thuecat_import_configuration']['columns']['configuration']['config'], [
            'ds_pointerField' => 'type',
            'ds' => [
                'default' => $placeholder,
                'static' => $placeholder,
                'syncScope' => $placeholder,
                'containsPlace' => $placeholder,
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
