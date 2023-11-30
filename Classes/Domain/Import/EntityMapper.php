<?php

declare(strict_types=1);

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Domain\Import;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\ArrayDenormalizer;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\CustomAnnotationExtractor;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\JsonDecode;
use WerkraumMedia\ThueCat\Domain\Import\EntityMapper\MappingException;

class EntityMapper
{
    /**
     * Returns mapped entity.
     * The returned object is of type $targetClassName
     */
    public function mapDataToEntity(
        array $jsonLD,
        string $targetClassName,
        array $context
    ): object {
        $serializer = $this->createSerializer();

        try {
            return $serializer->deserialize(
                json_encode($jsonLD),
                $targetClassName,
                'json',
                $context
            );
        } catch (Throwable $e) {
            throw new MappingException($jsonLD, $targetClassName, $e);
        }
    }

    private function createSerializer(): Serializer
    {
        return new Serializer(
            [
                new ArrayDenormalizer(),
                new DateTimeNormalizer(),
                new ObjectNormalizer(
                    null,
                    null,
                    null,
                    // We enforce following behaviour:
                    // 1. Try our own extractor to check for annotations on setter method.
                    // 2. Try to fetch info via reflection (e.g. by methods or property)
                    // 3. Use php doc as fallback
                    // We do this because of:
                    //  Most of the time we can just use the TypeHint of setter or add/remove for collections
                    //  Sometimes we have to deal with multiple types (e.g. string and array)
                    //  We then can have a different property name and no type hint, reflection will fail
                    //  But we can use PHPDoc to define all supported
                    //  And we can overrule the symfony behaviour first of all with our own extractor taking precedence.
                    //
                    // The reflection will first check mutator, then getter followed from properties.
                    // The phpdoc will first check the property itself.
                    new PropertyInfoExtractor(
                        [],
                        [
                            new CustomAnnotationExtractor(),
                            new ReflectionExtractor(),
                            new PhpDocExtractor(),
                        ]
                    )
                ),
            ],
            [
                new JsonEncoder(
                    null,
                    new JsonDecode()
                ),
            ]
        );
    }
}
