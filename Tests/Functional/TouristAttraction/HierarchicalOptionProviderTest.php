<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Service\FilterField\CategoryFilterField;
use WerkraumMedia\ThueCat\Service\FilterField\KeywordFilterField;
use WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\HierarchicalOptionProvider;

/**
 * The offered tree starts at the sets the scoped records actually use — the
 * ancestor whose own parent is the anchor — and each such set is offered with
 * its whole subtree, branches no record uses included.
 */
class HierarchicalOptionProviderTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilter.php';
    }

    #[Test]
    public function offersUsedSetsExpandedToTheirFullSubtree(): void
    {
        $options = $this->get(HierarchicalOptionProvider::class)->provide(
            new CategoryFilterField(),
            $this->scope()
        );

        // Museum and Kirche hang below the set Gebäudetyp, Haus is a set of its
        // own. Burg is offered although no record uses it: the set is offered
        // whole. Region/Innenstadt sit outside the anchor and never appear.
        self::assertSame(
            [
                'Gebäudetyp' => [
                    'Burg' => [],
                    'Kirche' => [],
                    'Museum' => [
                        'Freilichtmuseum' => [],
                    ],
                ],
                'Haus' => [],
            ],
            $this->flattenOptions($options)
        );
    }

    #[Test]
    public function offersTheKeywordTreeUnderItsOwnAnchor(): void
    {
        $options = $this->get(HierarchicalOptionProvider::class)->provide(
            new KeywordFilterField(),
            $this->scope()
        );

        // Records carry the leaves romantisch (502) and Erfurt (505); the sets
        // above them are Ambiente and Hauptstadt.
        self::assertSame(
            [
                'Ambiente' => [
                    'modern' => [],
                    'romantisch' => [],
                ],
                'Hauptstadt' => [
                    'Erfurt' => [],
                ],
            ],
            $this->flattenOptions($options)
        );
    }

    #[Test]
    public function unsetAnchorOffersNothing(): void
    {
        $options = $this->get(HierarchicalOptionProvider::class)->provide(
            new CategoryFilterField(),
            new FilterScope('tx_thuecat_tourist_attraction', [11], null)
        );

        self::assertSame([], $options->getOptions());
    }

    #[Test]
    public function everyOptionCarriesItsUidForTheCheckbox(): void
    {
        $options = $this->get(HierarchicalOptionProvider::class)->provide(
            new CategoryFilterField(),
            $this->scope()
        );

        $root = $options->getOptions()[0];
        self::assertSame('Gebäudetyp', $root->getTitle());
        self::assertSame(100, $root->getUid());
    }

    #[Test]
    public function expandsASelectedSetToItsDescendantsForMatching(): void
    {
        $uids = $this->get(HierarchicalOptionProvider::class)->descendantsOf(
            new CategoryFilterField(),
            [100]
        );

        // The set itself plus everything below it, so a record carrying only
        // the leaf Freilichtmuseum matches a selection of Gebäudetyp.
        sort($uids);
        self::assertSame([10, 11, 13, 15, 100], $uids);
    }

    private function scope(): FilterScope
    {
        return new FilterScope('tx_thuecat_tourist_attraction', [11], null, [
            CategoryAnchorSetting::CategoryParent->name => 300,
            CategoryAnchorSetting::KeywordParent->name => 500,
        ], [1, 10, 11]);
    }

    /**
     * @return array<string, mixed>
     */
    private function flattenOptions(FilterOptions $options): array
    {
        return $this->flattenEach($options->getOptions());
    }

    /**
     * @param FilterOption[] $options
     *
     * @return array<string, mixed>
     */
    private function flattenEach(array $options): array
    {
        $result = [];
        foreach ($options as $option) {
            $result[$option->getTitle()] = $this->flattenEach($option->getChildren());
        }

        return $result;
    }
}
