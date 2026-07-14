<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryMatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryUnmatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\DataHandlerError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\FetchingError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\MappingError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Organisation;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ParkingFacility;
use WerkraumMedia\ThueCat\Domain\Model\Backend\TouristInformation;
use WerkraumMedia\ThueCat\Domain\Model\Backend\Town;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Category as FrontendCategory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\OpeningHourSpecification;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\ParkingFacility as FrontendParkingFacility;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction as FrontendTouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town as FrontendTown;

return [
    Organisation::class => [
        'tableName' => 'tx_thuecat_organisation',
    ],
    Town::class => [
        'tableName' => 'tx_thuecat_town',
    ],
    TouristInformation::class => [
        'tableName' => 'tx_thuecat_tourist_information',
    ],
    ParkingFacility::class => [
        'tableName' => 'tx_thuecat_parking_facility',
    ],
    ImportConfiguration::class => [
        'tableName' => 'tx_thuecat_import_configuration',
    ],
    ImportLog::class => [
        'tableName' => 'tx_thuecat_import_log',
    ],
    ImportLogEntry::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'subclasses' => [
            'savingEntity' => SavingEntity::class,
            'fetchingError' => FetchingError::class,
            'dataHandlerError' => DataHandlerError::class,
            'mappingError' => MappingError::class,
            'categoryMatched' => CategoryMatched::class,
            'categoryUnmatched' => CategoryUnmatched::class,
        ],
    ],
    SavingEntity::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'savingEntity',
    ],
    FetchingError::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'fetchingError',
    ],
    DataHandlerError::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'dataHandlerError',
    ],
    MappingError::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'mappingError',
    ],
    CategoryMatched::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'categoryMatched',
    ],
    CategoryUnmatched::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'categoryUnmatched',
    ],

    FrontendTouristAttraction::class => [
        'tableName' => 'tx_thuecat_tourist_attraction',
    ],
    FrontendTown::class => [
        'tableName' => 'tx_thuecat_town',
    ],
    FrontendParkingFacility::class => [
        'tableName' => 'tx_thuecat_parking_facility',
    ],
    OpeningHourSpecification::class => [
        'tableName' => 'tx_thuecat_opening_hours',
    ],
    FrontendCategory::class => [
        'tableName' => 'sys_category',
    ],
];
