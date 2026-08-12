<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Progress;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\MediaFileDownloader;
use WerkraumMedia\ThueCat\Import\Progress\ImportProgressListener;
use WerkraumMedia\ThueCat\Import\Resolver;

/**
 * The importer must stay usable from the backend module, so the console may
 * only appear in the command that renders progress.
 */
class ImporterStaysConsoleFreeTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function importClasses(): array
    {
        return [
            'importer' => [Importer::class],
            'resolver' => [Resolver::class],
            'media downloader' => [MediaFileDownloader::class],
        ];
    }

    /**
     * @param class-string $className
     */
    #[Test]
    #[DataProvider('importClasses')]
    public function noConsoleTypeAppearsInConstructorOrMethods(string $className): void
    {
        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                foreach (self::typeNames($parameter->getType()) as $typeName) {
                    self::assertStringNotContainsString(
                        'Symfony\\Component\\Console',
                        $typeName,
                        sprintf('%s::%s() takes a console type.', $className, $method->getName())
                    );
                }
            }

            foreach (self::typeNames($method->getReturnType()) as $typeName) {
                self::assertStringNotContainsString(
                    'Symfony\\Component\\Console',
                    $typeName,
                    sprintf('%s::%s() returns a console type.', $className, $method->getName())
                );
            }
        }
    }

    #[Test]
    public function theListenerSeamItselfIsConsoleFree(): void
    {
        $reflection = new ReflectionClass(ImportProgressListener::class);

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                foreach (self::typeNames($parameter->getType()) as $typeName) {
                    self::assertStringNotContainsString('Symfony\\Component\\Console', $typeName);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function typeNames(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof ReflectionUnionType) {
            $names = [];
            foreach ($type->getTypes() as $inner) {
                $names = array_merge($names, self::typeNames($inner));
            }

            return $names;
        }

        return [];
    }
}
