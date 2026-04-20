<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;

class ImportEventsTest extends AbstractImportTestCase
{
    #[Test]
    public function importsFreshEvent(): void
    {
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/ImportsFreshEvent.php');
        GuzzleClientFaker::appendResponseFromFile(__DIR__ . '/Fixtures/Import/Guzzle/cdb.int.thuecat.org/resources/e_101155560-hubev.json');
        $this->importConfiguration();

        $this->assertPHPDataSet(__DIR__ . '/Assertions/Import/ImportsFreshEvent.php');
    }
}
