<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WerkraumMedia\ThueCat\Domain\Import\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Import\Importer;

class ImporterTest extends AbstractImportTestCase
{
    #[Test]
    public function importsFreshOrganization(): void
    {
        // End-to-end: Importer resolves UrlProvider → FetchData → Parser →
        // Resolver → DataHandler. The organisation has no outgoing relations,
        // so this exercises the full pipeline without dragging transient-bucket
        // resolution into the picture. Fetch + relation tests live alongside
        // the resolver.
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/BasicPages.php');
        GuzzleClientFaker::appendResponseFromFile(
            __DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json'
        );

        $configuration = new class () implements ImportConfiguration {
            public function getType(): string
            {
                return 'static';
            }

            public function getUrls(): array
            {
                return ['https://thuecat.org/resources/018132452787-ngbe'];
            }

            public function getAllowedTypes(): array
            {
                return [];
            }

            public function getApiKey(): string
            {
                return '';
            }

            public function getStoragePid(): int
            {
                return 10;
            }
        };

        $this->get(Importer::class)->importConfiguration($configuration);

        $row = $this->getContainer()
            ->get(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_thuecat_organisation')
            ->select('remote_id', 'title', 'pid')
            ->from('tx_thuecat_organisation')
            ->executeQuery()
            ->fetchAssociative()
        ;

        self::assertIsArray($row);
        self::assertSame('https://thuecat.org/resources/018132452787-ngbe', $row['remote_id']);
        self::assertSame('Erfurt Tourismus und Marketing GmbH', $row['title']);
        self::assertSame(10, $row['pid']);
    }
}
