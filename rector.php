<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withTypeCoverageLevel(63)
    ->withDeadCodeLevel(55)
    ->withTypeCoverageDocblockLevel(17)
    ->withCodingStyleLevel(27)
    ->withCodeQualityLevel(77)
    ->withBootstrapFiles([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_83)
    ->withAutoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
        phpunit: true
    )
    ->withImportNames(
        removeUnusedImports: true
    )
    ->withPreparedSets(
        carbon: true,
        privatization: true,
        earlyReturn: true,
        rectorPreset: true,
        naming: false,
        instanceOf: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyConfigs: true,
        symfonyCodeQuality: true,
    )
    ->withTreatClassesAsFinal()
    ->withFluentCallNewLine()
    ->withRealPathReporting()
    ->withComposerBased(
        laravel: true,
        symfony: true
    )
    ->withEditorUrl('vscode://file/%file:%line');
