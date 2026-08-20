<?php

declare(strict_types=1);

use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryMatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\CategoryUnmatched;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\DataHandlerError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\EffectiveSettings;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\EventDateSkipped;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\EventWithoutDates;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\FetchingError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\MappingError;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\ReferenceSkipped;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\ReferenceUnrelatable;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\RetriesRecovered;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\RunAborted;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\RunFailed;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\SavingEntity;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\ScheduleDayDropped;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry\ScheduleDaySkipped;
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
            'referenceSkipped' => ReferenceSkipped::class,
            'referenceUnrelatable' => ReferenceUnrelatable::class,
            'scheduleDaySkipped' => ScheduleDaySkipped::class,
            'scheduleDayDropped' => ScheduleDayDropped::class,
            'eventWithoutDates' => EventWithoutDates::class,
            'eventDateSkipped' => EventDateSkipped::class,
            'runAborted' => RunAborted::class,
            'runFailed' => RunFailed::class,
            'retriesRecovered' => RetriesRecovered::class,
            'effectiveSettings' => EffectiveSettings::class,
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
    EffectiveSettings::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'effectiveSettings',
    ],
    CategoryUnmatched::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'categoryUnmatched',
    ],
    ReferenceSkipped::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'referenceSkipped',
    ],
    ReferenceUnrelatable::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'referenceUnrelatable',
    ],
    ScheduleDaySkipped::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'scheduleDaySkipped',
    ],
    EventWithoutDates::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'eventWithoutDates',
    ],
    RunAborted::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'runAborted',
    ],
    RunFailed::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'runFailed',
    ],
    RetriesRecovered::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'retriesRecovered',
    ],
    ScheduleDayDropped::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'scheduleDayDropped',
    ],
    EventDateSkipped::class => [
        'tableName' => 'tx_thuecat_import_log_entry',
        'recordType' => 'eventDateSkipped',
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
