<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Import\EntityMapper;

use function in_array;
use InvalidArgumentException;
use LogicException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\PropertyInfo\Extractor\ConstructorArgumentTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

/**
 * A copy of symfonies own PhpDocExtractor class.
 * We only alter the order within getDocBlock() to execute checks in same order as the ReflectionExtractor.
 * That way we can check PhpDoc of mutators first.
 *
 * Make updating the file contents easier by keeping the original file contents as close as possible.
 */
class CustomAnnotationExtractor implements PropertyDescriptionExtractorInterface, PropertyTypeExtractorInterface, ConstructorArgumentTypeExtractorInterface
{
    public const PROPERTY = 0;
    public const ACCESSOR = 1;
    public const MUTATOR = 2;

    /**
     * @var array<string, array{DocBlock|null, int|null, string|null}>
     */
    private $docBlocks = [];

    /**
     * @var Context[]
     */
    private $contexts = [];

    private $docBlockFactory;
    private $contextFactory;
    private $phpDocTypeHelper;
    private $mutatorPrefixes;
    private $accessorPrefixes;
    private $arrayMutatorPrefixes;

    /**
     * @param string[]|null $mutatorPrefixes
     * @param string[]|null $accessorPrefixes
     * @param string[]|null $arrayMutatorPrefixes
     */
    public function __construct(DocBlockFactoryInterface $docBlockFactory = null, array $mutatorPrefixes = null, array $accessorPrefixes = null, array $arrayMutatorPrefixes = null)
    {
        if (!class_exists(DocBlockFactory::class)) {
            throw new LogicException(sprintf('Unable to use the "%s" class as the "phpdocumentor/reflection-docblock" package is not installed. Try running composer require "phpdocumentor/reflection-docblock".', __CLASS__));
        }

        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
        $this->phpDocTypeHelper = new PhpDocTypeHelper();
        $this->mutatorPrefixes = $mutatorPrefixes ?? ReflectionExtractor::$defaultMutatorPrefixes;
        $this->accessorPrefixes = $accessorPrefixes ?? ReflectionExtractor::$defaultAccessorPrefixes;
        $this->arrayMutatorPrefixes = $arrayMutatorPrefixes ?? ReflectionExtractor::$defaultArrayMutatorPrefixes;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortDescription(string $class, string $property, array $context = []): ?string
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class, $property);
        if (!$docBlock) {
            return null;
        }

        $shortDescription = $docBlock->getSummary();

        if (!empty($shortDescription)) {
            return $shortDescription;
        }

        foreach ($docBlock->getTagsByName('var') as $var) {
            if ($var && !$var instanceof InvalidTag) {
                $varDescription = $var->getDescription()->render();

                if (!empty($varDescription)) {
                    return $varDescription;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription(string $class, string $property, array $context = []): ?string
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class, $property);
        if (!$docBlock) {
            return null;
        }

        $contents = $docBlock->getDescription()->render();

        return $contents === '' ? null : $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        /** @var $docBlock DocBlock */
        [$docBlock, $source, $prefix] = $this->getDocBlock($class, $property);
        if (!$docBlock) {
            return null;
        }

        switch ($source) {
            case self::PROPERTY:
                $tag = 'var';
                break;

            case self::ACCESSOR:
                $tag = 'return';
                break;

            case self::MUTATOR:
                $tag = 'param';
                break;
        }

        $parentClass = null;
        $types = [];
        /** @var DocBlock\Tags\Var_|DocBlock\Tags\Return_|DocBlock\Tags\Param $tag */
        foreach ($docBlock->getTagsByName($tag) as $tag) {
            if ($tag && !$tag instanceof InvalidTag && $tag->getType() !== null) {
                foreach ($this->phpDocTypeHelper->getTypes($tag->getType()) as $type) {
                    switch ($type->getClassName()) {
                        case 'self':
                        case 'static':
                            $resolvedClass = $class;
                            break;

                        case 'parent':
                            if (false !== $resolvedClass = $parentClass ?? $parentClass = get_parent_class($class)) {
                                break;
                            }
                            // no break

                        default:
                            $types[] = $type;
                            continue 2;
                    }

                    $types[] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->isNullable(), $resolvedClass, $type->isCollection(), $type->getCollectionKeyTypes(), $type->getCollectionValueTypes());
                }
            }
        }

        if (!isset($types[0])) {
            return null;
        }

        if (!in_array($prefix, $this->arrayMutatorPrefixes)) {
            return $types;
        }

        return [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), $types[0])];
    }

    /**
     * {@inheritdoc}
     */
    public function getTypesFromConstructor(string $class, string $property): ?array
    {
        $docBlock = $this->getDocBlockFromConstructor($class, $property);

        if (!$docBlock) {
            return null;
        }

        $types = [];
        /** @var DocBlock\Tags\Var_|DocBlock\Tags\Return_|DocBlock\Tags\Param $tag */
        foreach ($docBlock->getTagsByName('param') as $tag) {
            if ($tag && $tag->getType() !== null) {
                $types[] = $this->phpDocTypeHelper->getTypes($tag->getType());
            }
        }

        if (!isset($types[0]) || $types[0] === []) {
            return null;
        }

        return array_merge([], ...$types);
    }

    private function getDocBlockFromConstructor(string $class, string $property): ?DocBlock
    {
        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            return null;
        }
        $reflectionConstructor = $reflectionClass->getConstructor();
        if (!$reflectionConstructor) {
            return null;
        }

        try {
            $docBlock = $this->docBlockFactory->create($reflectionConstructor, $this->contextFactory->createFromReflector($reflectionConstructor));

            return $this->filterDocBlockParams($docBlock, $property);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    private function filterDocBlockParams(DocBlock $docBlock, string $allowedParam): DocBlock
    {
        $tags = array_values(array_filter($docBlock->getTagsByName('param'), function ($tag) use ($allowedParam) {
            return $tag instanceof Param && $allowedParam === $tag->getVariableName();
        }));

        return new DocBlock(
            $docBlock->getSummary(),
            $docBlock->getDescription(),
            $tags,
            $docBlock->getContext(),
            $docBlock->getLocation(),
            $docBlock->isTemplateStart(),
            $docBlock->isTemplateEnd()
        );
    }

    /**
     * @return array{DocBlock|null, int|null, string|null}
     */
    private function getDocBlock(string $class, string $property): array
    {
        $propertyHash = sprintf('%s::%s', $class, $property);

        if (isset($this->docBlocks[$propertyHash])) {
            return $this->docBlocks[$propertyHash];
        }

        $ucFirstProperty = ucfirst($property);

        switch (true) {
            case [$docBlock, $prefix] = $this->getDocBlockFromMethod($class, $ucFirstProperty, self::MUTATOR):
                $data = [$docBlock, self::MUTATOR, $prefix];
                break;

            case [$docBlock] = $this->getDocBlockFromMethod($class, $ucFirstProperty, self::ACCESSOR):
                $data = [$docBlock, self::ACCESSOR, null];
                break;

            case $docBlock = $this->getDocBlockFromProperty($class, $property):
                $data = [$docBlock, self::PROPERTY, null];
                break;

            default:
                $data = [null, null, null];
        }

        return $this->docBlocks[$propertyHash] = $data;
    }

    private function getDocBlockFromProperty(string $class, string $property): ?DocBlock
    {
        // Use a ReflectionProperty instead of $class to get the parent class if applicable
        try {
            $reflectionProperty = new ReflectionProperty($class, $property);
        } catch (ReflectionException $e) {
            return null;
        }

        $reflector = $reflectionProperty->getDeclaringClass();

        foreach ($reflector->getTraits() as $trait) {
            if ($trait->hasProperty($property)) {
                return $this->getDocBlockFromProperty($trait->getName(), $property);
            }
        }

        try {
            return $this->docBlockFactory->create($reflectionProperty, $this->createFromReflector($reflector));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return null;
        }
    }

    /**
     * @return array{DocBlock, string}|null
     */
    private function getDocBlockFromMethod(string $class, string $ucFirstProperty, int $type): ?array
    {
        $prefixes = $type === self::ACCESSOR ? $this->accessorPrefixes : $this->mutatorPrefixes;
        $prefix = null;

        foreach ($prefixes as $prefix) {
            $methodName = $prefix . $ucFirstProperty;

            try {
                $reflectionMethod = new ReflectionMethod($class, $methodName);
                if ($reflectionMethod->isStatic()) {
                    continue;
                }

                if (
                    ($type === self::ACCESSOR && $reflectionMethod->getNumberOfRequiredParameters() === 0) ||
                    ($type === self::MUTATOR && $reflectionMethod->getNumberOfParameters() >= 1)
                ) {
                    break;
                }
            } catch (ReflectionException $e) {
                // Try the next prefix if the method doesn't exist
            }
        }

        if (!isset($reflectionMethod)) {
            return null;
        }

        $reflector = $reflectionMethod->getDeclaringClass();

        foreach ($reflector->getTraits() as $trait) {
            if ($trait->hasMethod($methodName)) {
                return $this->getDocBlockFromMethod($trait->getName(), $ucFirstProperty, $type);
            }
        }

        try {
            return [$this->docBlockFactory->create($reflectionMethod, $this->createFromReflector($reflector)), $prefix];
        } catch (InvalidArgumentException|RuntimeException $e) {
            return null;
        }
    }

    /**
     * Prevents a lot of redundant calls to ContextFactory::createForNamespace().
     */
    private function createFromReflector(ReflectionClass $reflector): Context
    {
        $cacheKey = $reflector->getNamespaceName() . ':' . $reflector->getFileName();

        if (isset($this->contexts[$cacheKey])) {
            return $this->contexts[$cacheKey];
        }

        $this->contexts[$cacheKey] = $this->contextFactory->createFromReflector($reflector);

        return $this->contexts[$cacheKey];
    }
}
