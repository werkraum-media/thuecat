<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\CategoryConfigurationException;
use WerkraumMedia\ThueCat\Import\ImportConfigurationValidator;
use WerkraumMedia\ThueCat\Import\StoragePidConfigurationException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// Pre-flight validation of the import configuration. storagePid 10 sits in the
// 'example' site (rootPageId 1); category anchors 100/pid 20 are in-site, 900/
// pid 91 and page 500 are out of it. See ValidatorScopePreState.
class ImportConfigurationValidatorTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/ValidatorScopePreState.php');
    }

    #[Test]
    public function passesWhenNoCategoryConfigured(): void
    {
        $this->validate(10, 0, 0);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function passesWhenCategoryAnchorsAreInSite(): void
    {
        $this->validate(10, 100, 20);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function throwsWhenStoragePidHasNoSite(): void
    {
        $this->expectException(StoragePidConfigurationException::class);
        $this->expectExceptionCode(1752570000);
        $this->validate(500, 0, 0);
    }

    #[Test]
    public function throwsWhenParentSetButStorageMissing(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570001);
        $this->validate(10, 100, 0);
    }

    #[Test]
    public function throwsWhenStorageSetButParentMissing(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570001);
        $this->validate(10, 0, 20);
    }

    #[Test]
    public function throwsWhenCategoryStoragePidOutsideSite(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570002);
        // parent 100 is in-site, but categoryStoragePid 91 is on the other site.
        $this->validate(10, 100, 91);
    }

    #[Test]
    public function throwsWhenCategoryParentOutsideSite(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570003);
        // storage 20 is in-site, but parent 900 lives on the other site.
        $this->validate(10, 900, 20);
    }

    private function validate(int $storagePid, int $categoryParent, int $categoryStoragePid): void
    {
        $configuration = $this->configuration($storagePid, $categoryParent, $categoryStoragePid);
        $this->get(ImportConfigurationValidator::class)->validate($configuration);
    }

    private function configuration(
        int $storagePid,
        int $categoryParent,
        int $categoryStoragePid
    ): ImportConfigurationInterface {
        return new class ($storagePid, $categoryParent, $categoryStoragePid) implements ImportConfigurationInterface {
            public function __construct(
                private readonly int $storagePid,
                private readonly int $categoryParent,
                private readonly int $categoryStoragePid,
            ) {
            }

            public function getStoragePid(): int
            {
                return $this->storagePid;
            }

            public function getCategoryParent(): int
            {
                return $this->categoryParent;
            }

            public function getCategoryStoragePid(): int
            {
                return $this->categoryStoragePid;
            }

            public function getType(): string
            {
                return 'static';
            }

            public function getUrls(): array
            {
                return [];
            }

            public function getAllowedTypes(): array
            {
                return [];
            }

            public function getApiKey(): string
            {
                return '';
            }

            public function getFileFolder(): string
            {
                return '';
            }

            public function getApiDomain(): string
            {
                return '';
            }

            public function getImportTarget(): string
            {
                return 'thuecat';
            }

            public function getUid(): ?int
            {
                return 1;
            }

            public function getFetchLastXDays(): int
            {
                return 0;
            }
        };
    }
}