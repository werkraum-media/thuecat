<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ImporterTest extends AbstractImportTestCase
{


    public function importsFreshOrganization(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshOrganization.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/thuecat.org/resources/018132452787-ngbe.json');

        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshOrganization.php');
    }
}