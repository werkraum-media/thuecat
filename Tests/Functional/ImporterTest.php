<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;

class ImporterTest extends AbstractImportTestCase
{
    #[Test]
    public function importsFreshOrganization(): void
    {
        self::markTestSkipped('we will come to that after parser and resolver are done');
    }
}
