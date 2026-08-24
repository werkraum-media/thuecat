<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface;

class AbstractImportTestCase extends TestCase
{
    protected string $fixturePath = __DIR__ . '/../Fixtures/';

    /**
     * A parent stages children of several tables (opening hours, addresses, …),
     * so a test asserting one kind must select it rather than count them all.
     *
     * @return list<array<string, string|int|float>>
     */
    protected function childRowsOf(EntityInterface $entity, string $table): array
    {
        $rows = [];
        foreach ($entity->getChildren() as $child) {
            if ($child::TABLE === $table) {
                $rows[] = $child->toArray();
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function nodeFromFixture(string $filename, string $nodeName): ?array
    {
        $path = $this->fixturePath . $filename;
        $raw = file_get_contents($path);
        self::assertIsString($raw, 'Fixture not readable: ' . $path);
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return null;
        }
        $graph = $decoded['@graph'] ?? [];
        if (!is_array($graph)) {
            return null;
        }
        foreach ($graph as $node) {
            if (!is_array($node)) {
                continue;
            }
            $types = $node['@type'] ?? [];
            if (is_array($types) && in_array($nodeName, $types, true)) {
                /** @var array<string, mixed> $node JSON objects decode to string-keyed arrays. */
                return $node;
            }
        }
        return null;
    }

    /**
     * Decode a JSON blob produced by an entity's toArray() value. Keeps phpstan
     * happy at call sites by narrowing the mixed array-offset access to a real
     * array.
     *
     * @return array<int|string, mixed>
     */
    protected function decodeJson(mixed $raw): array
    {
        self::assertIsString($raw);
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        return $decoded;
    }
}
