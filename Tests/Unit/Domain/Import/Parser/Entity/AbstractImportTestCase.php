<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\TestCase;

class AbstractImportTestCase extends TestCase
{
    protected string $fixturePath = __DIR__ . '/../Fixtures/';

    protected function nodeFromFixture(string $filename, string $nodeName): ?array
    {
        $path = $this->fixturePath . $filename;
        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        foreach ($graph as $node) {
            if (is_array($node) && in_array($nodeName, $node['@type'] ?? [], true)) {
                return $node;
            }
        }
        return null;
    }
}
