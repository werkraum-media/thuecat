<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\ClassMethodArrayDocblockParamFromLocalCallsRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromAssignsParamToParamReferenceRector;
use Ssch\TYPO3Rector\CodeQuality\General\RemoveTypo3VersionChecksRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO311\v0\DateTimeAspectInsteadOfGlobalsExecTimeRector;

// The level set up to v14, without the files that register rules rector deprecated:
// v10/typo3-100, v10/typo3-104, v11/typo3-115, v13/strict-types and v14/strict-types.
$typo3Sets = __DIR__ . '/vendor/ssch/typo3-rector/config';

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Build',
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
        __DIR__ . '/ext_localconf.php',
    ])
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withPhpSets()
    ->withAttributesSets(all: true)
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        phpunitMockToStub: true,
        phpunitNarrowAsserts: true,
    )
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ])
    ->withPHPStanConfigs([Typo3Option::PHPSTAN_FOR_RECTOR_PATH])
    ->withImportNames(importShortClasses: false)
    ->withConfiguredRule(RemoveTypo3VersionChecksRector::class, [
        RemoveTypo3VersionChecksRector::TARGET_VERSION => 14,
    ])
    ->withSkip([
        AddSeeTestAnnotationRector::class,
        // The frontend application rebuilds the date aspect from EXEC_TIME on every request.
        DateTimeAspectInsteadOfGlobalsExecTimeRector::class => [
            __DIR__ . '/Tests/Functional/TouristAttraction/TouristAttractionShowTest.php',
        ],
        // Infers mixed[][] from the one nested assignment; the DataHandler field array holds scalars too.
        AddParamArrayDocblockFromAssignsParamToParamReferenceRector::class => [
            __DIR__ . '/Classes/Typo3/Hook/AddTitleForStaticUrlsDataHandlerHook.php',
        ],
        // Extbase types a property without @var after its same-named getter; an array getter blanks the string.
        RemoveUselessVarTagRector::class => [
            __DIR__ . '/Classes/Domain/Model',
        ],
        // contradicts phpstan on format of dataMap
        ClassMethodArrayDocblockParamFromLocalCallsRector::class => [
            __DIR__ . '/Tests/Functional/Typo3/Hook/AddTitleForStaticUrlsDataHandlerHookTest.php',
        ],
        // assertions are performed on teardown, rector just doesn't recognizes this
        AddDoesNotPerformAssertionToNonAssertingTestRector::class => [
            __DIR__ . '/Tests/Functional/Importer/FetchDataTest.php',
        ],
        // this is just bogus, short exception names are fine
        CatchExceptionNameMatchingTypeRector::class,
    ])
;
