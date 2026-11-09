<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\String_\SimplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclarationDocblocks\Rector\Class_\ClassMethodArrayDocblockParamFromLocalCallsRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromAssignsParamToParamReferenceRector;
use Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromDimFetchAccessRector;
use Ssch\TYPO3Rector\CodeQuality\General\RemoveTypo3VersionChecksRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO311\v0\DateTimeAspectInsteadOfGlobalsExecTimeRector;
use Ssch\TYPO3Rector\TYPO312\v0\RemoveObsoleteAppearanceConfigRector;

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
    )
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        // currently not working due to https://github.com/sabbelasichon/typo3-rector/pull/4955,
        // @todo also runs into timeout. Have a look again after merge.
        // Typo3LevelSetList::UP_TO_TYPO3_14,
        $typo3Sets . '/v10/tca-100.php',
        $typo3Sets . '/v10/tca-104.php',
        $typo3Sets . '/v10/typo3-101.php',
        $typo3Sets . '/v10/typo3-102.php',
        $typo3Sets . '/v10/typo3-103.php',
        $typo3Sets . '/v10/rename-classes.php',
        $typo3Sets . '/v10/use-constants-from-typo3-database-connection.php',
        $typo3Sets . '/v11/tca-110.php',
        $typo3Sets . '/v11/tca-113.php',
        $typo3Sets . '/v11/tca-114.php',
        $typo3Sets . '/v11/tca-115.php',
        $typo3Sets . '/v11/typo3-110.php',
        $typo3Sets . '/v11/typo3-112.php',
        $typo3Sets . '/v11/typo3-113.php',
        $typo3Sets . '/v11/typo3-114.php',
        $typo3Sets . '/v12/tca-120.php',
        $typo3Sets . '/v12/tca-123.php',
        $typo3Sets . '/v12/typo3-120.php',
        $typo3Sets . '/v12/typo3-121.php',
        $typo3Sets . '/v12/typo3-122.php',
        $typo3Sets . '/v12/typo3-123.php',
        $typo3Sets . '/v12/typo3-124.php',
        $typo3Sets . '/v13/introduce-capabilities-bit-set.php',
        $typo3Sets . '/v13/tca-130.php',
        $typo3Sets . '/v13/typo3-130.php',
        $typo3Sets . '/v13/typo3-130-extbase-hash-service-core-hash-service.php',
        $typo3Sets . '/v13/typo3-131.php',
        $typo3Sets . '/v13/typo3-132.php',
        $typo3Sets . '/v13/typo3-133.php',
        $typo3Sets . '/v13/typo3-134.php',
        $typo3Sets . '/v14/rename-classes.php',
        $typo3Sets . '/v14/tca-140.php',
        $typo3Sets . '/v14/typo3-140.php',
        $typo3Sets . '/v14/typo3-142.php',
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

        // Disabled until enabled one by one; the count is the files each rule changed.
        // 149 files
        FinalizeTestCaseClassRector::class,
        // 110 files
        NewlineAfterStatementRector::class,
        // 62 files
        NewMethodCallWithoutParenthesesRector::class,
        // 59 files
        AddTypeToConstRector::class,
        // 46 files
        NewlineBetweenClassLikeStmtsRector::class,
        // 31 files
        ReadOnlyClassRector::class,
        // 21 files
        ClosureToArrowFunctionRector::class,
        // 20 files
        LogicalToBooleanRector::class,
        // 18 files
        ClosureReturnTypeRector::class,
        // 16 files
        AddArrowFunctionReturnTypeRector::class,
        // 16 files
        NewlineBeforeNewAssignSetRector::class,
        // 16 files
        ExplicitNullableObjectInstanceRector::class,
        // 12 files
        SimplifyQuoteEscapeRector::class,
        // 10 files
        YieldDataProviderRector::class,
        // 10 files
        CatchExceptionNameMatchingTypeRector::class,
        // 8 files
        AddParamArrayDocblockFromDimFetchAccessRector::class,
        // 6 files
        RemoveObsoleteAppearanceConfigRector::class,
    ])
;
