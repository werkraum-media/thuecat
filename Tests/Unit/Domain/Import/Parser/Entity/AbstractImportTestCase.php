<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\TestCase;

class AbstractImportTestCase extends TestCase
{
    protected string $fixturePath = __DIR__ . '/../Fixtures/';

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
