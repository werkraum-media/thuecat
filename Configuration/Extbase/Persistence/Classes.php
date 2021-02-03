<?php

return [
    \WerkraumMedia\ThueCat\Domain\Model\Backend\Organisation::class => [
        'tableName' => 'tx_thuecat_organisation',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Backend\Town::class => [
        'tableName' => 'tx_thuecat_town',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Backend\TouristInformation::class => [
        'tableName' => 'tx_thuecat_tourist_information',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration::class => [
        'tableName' => 'tx_thuecat_import_configuration',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog::class => [
        'tableName' => 'tx_thuecat_import_log',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction::class => [
        'tableName' => 'tx_thuecat_tourist_attraction',
    ],
    \WerkraumMedia\ThueCat\Domain\Model\Frontend\Town::class => [
        'tableName' => 'tx_thuecat_town',
    ],
];
