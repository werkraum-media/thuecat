<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer;
use PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $services = $containerConfigurator->services();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/Classes/',
        __DIR__ . '/Configuration/',
        __DIR__ . '/Tests/',
        __DIR__ . '/ecs.php',
        __DIR__ . '/ext_emconf.php',
    ]);

    $parameters->set(Option::SETS, [
        SetList::PSR_12,
        SetList::PHPUNIT,
    ]);

    $parameters->set(Option::SKIP, [
        DeclareStrictTypesFixer::class => [
            __DIR__ . '/Configuration/',
            __DIR__ . '/ext_emconf.php',
        ],
    ]);

    $services->set(DeclareStrictTypesFixer::class);

    $services->set(NoUnusedImportsFixer::class);
    $services->set(FullyQualifiedStrictTypesFixer::class);
    $services->set(NoMultilineWhitespaceAroundDoubleArrowFixer::class);
    $services->set(ArraySyntaxFixer::class)->call('configure', [[
        'syntax' => 'short',
    ]]);
    $services->set(SingleQuoteFixer::class);
    $services->set(TrailingCommaInMultilineArrayFixer::class);

    $services->set(PhpUnitTestAnnotationFixer::class)->call('configure', [[
        'style' => 'annotation',
    ]]);
    $services->set(ClassAttributesSeparationFixer::class)->call('configure', [[
        'elements' => [
            'const' => 'one',
            'method' => 'one',
            'property' => 'one',
        ],
    ]]);
};
