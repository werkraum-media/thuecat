<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\CategoryConfigurationException;
use WerkraumMedia\ThueCat\Import\ImportConfigurationValidator;
use WerkraumMedia\ThueCat\Import\KeywordConfigurationException;
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

    #[Test]
    public function passesWhenNoKeywordConfigured(): void
    {
        $this->validate(10, 100, 20, 0, 0);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function passesWhenKeywordAnchorsAreInSite(): void
    {
        $this->validate(10, 0, 0, 100, 20);
        $this->addToAssertionCount(1);
    }

    // Both properties configured at once must not interfere: they share the
    // anchor rules but are validated separately.
    #[Test]
    public function passesWhenCategoryAndKeywordAreBothConfigured(): void
    {
        $this->validate(10, 100, 20, 100, 20);
        $this->addToAssertionCount(1);
    }

    // An unusable category must not be masked by a valid keyword configuration.
    #[Test]
    public function throwsForCategoryEvenWhenKeywordIsValid(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570001);
        $this->validate(10, 100, 0, 100, 20);
    }

    #[Test]
    public function throwsWhenKeywordParentSetButStorageMissing(): void
    {
        $this->expectException(KeywordConfigurationException::class);
        $this->expectExceptionCode(1786713820);
        $this->validate(10, 0, 0, 100, 0);
    }

    #[Test]
    public function throwsWhenKeywordStorageSetButParentMissing(): void
    {
        $this->expectException(KeywordConfigurationException::class);
        $this->expectExceptionCode(1786713820);
        $this->validate(10, 0, 0, 0, 20);
    }

    #[Test]
    public function throwsWhenKeywordStoragePidOutsideSite(): void
    {
        $this->expectException(KeywordConfigurationException::class);
        $this->expectExceptionCode(1786713821);
        $this->validate(10, 0, 0, 100, 91);
    }

    #[Test]
    public function throwsWhenKeywordParentOutsideSite(): void
    {
        $this->expectException(KeywordConfigurationException::class);
        $this->expectExceptionCode(1786713822);
        $this->validate(10, 0, 0, 900, 20);
    }

    private function validate(
        int $storagePid,
        int $categoryParent,
        int $categoryStoragePid,
        int $keywordParent = 0,
        int $keywordStoragePid = 0
    ): void {
        $configuration = $this->configuration(
            $storagePid,
            $categoryParent,
            $categoryStoragePid,
            $keywordParent,
            $keywordStoragePid
        );
        $this->get(ImportConfigurationValidator::class)->validate($configuration);
    }

    private function configuration(
        int $storagePid,
        int $categoryParent,
        int $categoryStoragePid,
        int $keywordParent = 0,
        int $keywordStoragePid = 0
    ): ImportConfigurationInterface {
        return new class($storagePid, $categoryParent, $categoryStoragePid, $keywordParent, $keywordStoragePid) implements ImportConfigurationInterface {
            public function __construct(
                private readonly int $storagePid,
                private readonly int $categoryParent,
                private readonly int $categoryStoragePid,
                private readonly int $keywordParent,
                private readonly int $keywordStoragePid,
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

            public function getKeywordParent(): int
            {
                return $this->keywordParent;
            }

            public function getKeywordStoragePid(): int
            {
                return $this->keywordStoragePid;
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

            // @phpstan-ignore return.unusedType (interface is nullable; stub always has a uid)
            public function getUid(): ?int
            {
                return 1;
            }

            public function getFetchLastXDays(): int
            {
                return 0;
            }

            public function getRunBudget(): int
            {
                return 0;
            }

            public function getFetchCacheLifetime(): int
            {
                return 0;
            }
        };
    }
}
