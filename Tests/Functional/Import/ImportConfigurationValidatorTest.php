<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfigurationInterface;
use WerkraumMedia\ThueCat\Import\CategoryConfigurationException;
use WerkraumMedia\ThueCat\Import\ImportConfigurationValidator;
use WerkraumMedia\ThueCat\Import\ImportTargetConfigurationException;
use WerkraumMedia\ThueCat\Import\KeywordConfigurationException;
use WerkraumMedia\ThueCat\Import\StoragePidConfigurationException;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportConfigurationTestCase;

// Pre-flight validation of the import configuration. storagePid 10 sits in the
// site rooted at page 1; category anchors 100/pid 20 are in-site, 900/pid 91
// and page 500 are out of it. See ValidatorScopePreState.
class ImportConfigurationValidatorTest extends AbstractImportConfigurationTestCase
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

    // The instance-wide fallback is one value across every site, so on a
    // multi-site instance it is inside at most one of them.
    #[Test]
    public function throwsWhenInstanceWideFallbackPointsOutsideSite(): void
    {
        $this->writeSiteSettings([], 'validator_scope', 1);
        $this->writeExtensionConfiguration([
            'importThuecatCategoryParent' => '900',
            'importThuecatCategoryStoragePid' => '91',
        ]);

        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570002);
        $this->get(ImportConfigurationValidator::class)->validate($this->configuration(10));
    }

    /**
     * An unknown target must fail rather than resolve anchors nobody declared:
     * that would switch every kind's mapping off and let the run report success
     * having imported no categories.
     */
    #[Test]
    public function throwsWhenImportTargetIsUnknown(): void
    {
        $this->writeSiteSettings([], 'validator_scope', 1);

        $this->expectException(ImportTargetConfigurationException::class);
        $this->expectExceptionCode(1787117122);
        $this->get(ImportConfigurationValidator::class)
            ->validate($this->configuration(10, 'future'))
        ;
    }

    #[Test]
    public function theUnknownTargetMessageNamesTheValueAndTheAcceptedOnes(): void
    {
        $this->writeSiteSettings([], 'validator_scope', 1);

        try {
            $this->get(ImportConfigurationValidator::class)
                ->validate($this->configuration(10, 'future'))
            ;
            self::fail('An unknown import target must not pass validation.');
        } catch (ImportTargetConfigurationException $e) {
            self::assertStringContainsString('future', $e->getMessage());
            self::assertStringContainsString('thuecat', $e->getMessage());
            self::assertStringContainsString('events', $e->getMessage());
        }
    }

    /**
     * An absent target is not unknown: it means the thuecat target, so an
     * empty value must resolve the thuecat anchors — hence settings written
     * under 'thuecat' while the configuration reports ''.
     */
    #[Test]
    public function passesWhenImportTargetIsEmpty(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => ['category' => ['storagePid' => 20, 'parent' => 100]],
            ],
        ], 'validator_scope', 1);

        $this->get(ImportConfigurationValidator::class)->validate($this->configuration(10, ''));
        $this->addToAssertionCount(1);
    }

    // Each target is validated against its own anchors, in its own tree.
    #[Test]
    public function validatesTheAnchorsOfTheConfiguredTarget(): void
    {
        $this->validate(10, 100, 20, 0, 0, 'events');
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function throwsForTheConfiguredTargetsOutOfSiteAnchor(): void
    {
        $this->expectException(CategoryConfigurationException::class);
        $this->expectExceptionCode(1752570003);
        $this->validate(10, 900, 20, 0, 0, 'events');
    }

    /**
     * Anchors declared for one target say nothing about the other: the run must
     * see them as unset, not borrow them into its own tree.
     */
    #[Test]
    public function passesWhenOnlyTheOtherTargetIsConfigured(): void
    {
        $this->writeSiteSettings([
            'import' => [
                'thuecat' => [
                    'category' => ['storagePid' => 20, 'parent' => 100],
                    'keywords' => ['storagePid' => 20, 'parent' => 100],
                ],
            ],
        ], 'validator_scope', 1);

        $this->get(ImportConfigurationValidator::class)
            ->validate($this->configuration(10, 'events'))
        ;
        $this->addToAssertionCount(1);
    }

    /**
     * The anchors come from the site owning $storagePid. 0 stays "unset": the
     * resolver skips a level that supplies nothing usable, so an omitted
     * setting and a 0 are the same case.
     */
    private function validate(
        int $storagePid,
        int $categoryParent,
        int $categoryStoragePid,
        int $keywordParent = 0,
        int $keywordStoragePid = 0,
        string $importTarget = 'thuecat'
    ): void {
        $this->writeSiteSettings([
            'import' => [
                $importTarget => [
                    'category' => ['storagePid' => $categoryStoragePid, 'parent' => $categoryParent],
                    'keywords' => ['storagePid' => $keywordStoragePid, 'parent' => $keywordParent],
                ],
            ],
        ], 'validator_scope', 1);

        $this->get(ImportConfigurationValidator::class)
            ->validate($this->configuration($storagePid, $importTarget))
        ;
    }

    private function configuration(int $storagePid, string $importTarget = 'thuecat'): ImportConfigurationInterface
    {
        return new class($storagePid, $importTarget) implements ImportConfigurationInterface {
            public function __construct(
                private readonly int $storagePid,
                private readonly string $importTarget = 'thuecat',
            ) {
            }

            public function getStoragePid(): int
            {
                return $this->storagePid;
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
                return $this->importTarget;
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
