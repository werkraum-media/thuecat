<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;

class ImporterTest extends AbstractImportTestCase
{


    #[Test]
    public function importsFreshOrganization(): void
    {
        $this->markTestSkipped('we will come to that after parser and resolver are done');
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }

    private function importConfiguration(): void
    {
        $this->workaroundExtbaseConfiguration();

        $this->get(Context::class)->setAspect(
            'date',
            new DateTimeAspect(
                new DateTimeImmutable('2024-03-03 00:00:00', new DateTimeZone('UTC'))
            )
        );

        $configuration = $this->get(ImportConfigurationRepository::class)->findByUid(1);
        self::assertInstanceOf(ImportConfiguration::class, $configuration);
        $this->get(Importer::class)->importConfiguration($configuration);
    }
}