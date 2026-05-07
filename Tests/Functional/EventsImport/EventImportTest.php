<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\EventsImport;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// End-to-end import test: stages a static-URL ImportConfiguration that points
// at the Kreuzchor JSON-LD fixture, runs the Importer, and asserts that the
// event row plus its single child Date row land in the ext:events tables
// with the FK wired correctly. Proves the EventEntity → DateEntity →
// Resolver → DataHandler chain works through the existing pipeline.
class EventImportTest extends AbstractImportTestCase
{
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
        'werkraummedia/events/',
    ];

    // Override fixture roots: events fixtures live under the events test tree,
    // not the legacy thuecat one. The Guzzle fixtures sit under
    // EventsImport/Fixtures/Guzzle/ keyed by host + path.
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Guzzle';
    protected string $fixtureDomain = 'cdb.int.thuecat.org';
    protected string $fixturePath = 'api/resources';

    #[Test]
    public function importsKreuzchorEventWithSingleDate(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/EventImportKreuzchorPreState.php');
        $this->expectFetch('e_19542-hubev.json');

        $this->workaroundExtbaseConfiguration();
        $configuration = $this->get(ImportConfigurationRepository::class)->findOneByUid(1);
        self::assertNotNull($configuration, 'Kreuzchor import configuration not found in pre-state.');
        $this->get(Importer::class)->importConfiguration($configuration);

        $this->assertPHPDataSet(__DIR__ . '/Assertions/EventImportKreuzchor.php');
    }
}
