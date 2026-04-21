<?php

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\OrganisationEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\TouristInformationEntity;
use PHPUnit\Framework\TestCase;

class TouristInformationEntityTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../Fixtures/';
    #[Test]
    public function returnsCorrectTable(): void
    {
        $entity = new TouristInformationEntity(['@id' => 'https://thuecat.org/resources/333039283321-xxwg'], new DataHandlerPayload());
        self::assertSame('tx_thuecat_tourist_information', $entity->table);
    }

    #[Test]
    public function rowContainsRemoteId(): void
    {
        $node = $this->nodeFromFixture('333039283321-xxwg.json');
        self::assertNotNull($node);
        $subject = new TouristInformationEntity($node, new DataHandlerPayload());

        $row = $subject->toArray();

        self::assertSame('https://thuecat.org/resources/333039283321-xxwg', $row['remote_id']);
    }

    private function nodeFromFixture(string $filename): ?array
    {
        $path = self::FIXTURE_PATH . $filename;
        $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $graph = is_array($decoded) ? $decoded['@graph'] : [];
        foreach ($graph as $node) {
            if (is_array($node) && in_array('schema:Organization', $node['@type'] ?? [], true)) {
                return $node;
            }
        }
        return null;
    }
}
