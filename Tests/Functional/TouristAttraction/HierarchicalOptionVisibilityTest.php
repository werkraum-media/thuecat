<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOption;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
use WerkraumMedia\ThueCat\Service\FilterField\CategoryFilterField;
use WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\HierarchicalOptionProvider;

/**
 * The recursive expansion runs as raw SQL, out of reach of the restriction
 * container, and walks a table an editor can put a cycle into. Both are
 * therefore asserted rather than assumed.
 */
class HierarchicalOptionVisibilityTest extends AbstractFrontendTestCase
{
    protected function getDataSetFileName(): string
    {
        return 'TouristAttractionsForFilterOptions.php';
    }

    #[Test]
    public function doesNotOfferAHiddenValueBelowAUsedSet(): void
    {
        $titles = $this->offeredTitles();

        self::assertContains('Museum', $titles);
        self::assertNotContains('Versteckte Kategorie', $titles);
    }

    #[Test]
    public function dropsAValueFromAnotherSiteEvenWhenItClimbsToOurAnchor(): void
    {
        $titles = $this->offeredTitles();

        // Parented under the used set Museum, so its rootline reaches our
        // anchor exactly like a local value. Being in another site is the only
        // thing that excludes it.
        self::assertContains('Museum', $titles);
        self::assertNotContains('Kategorie der anderen Site', $titles);
    }

    #[Test]
    public function terminatesOnAParentCycle(): void
    {
        $uids = $this->get(HierarchicalOptionProvider::class)->descendantsOf(
            new CategoryFilterField(),
            [305]
        );

        // Reaching this line at all is the assertion: an unbounded recursive
        // term would never return. Each member of the cycle appears once.
        sort($uids);
        self::assertSame([305, 306], $uids);
    }

    /**
     * Every title the category field offers, at any depth.
     *
     * @return list<string>
     */
    private function offeredTitles(): array
    {
        $options = $this->get(HierarchicalOptionProvider::class)->provide(
            new CategoryFilterField(),
            new FilterScope('tx_thuecat_tourist_attraction', [11], null, [
                CategoryAnchorSetting::CategoryParent->name => 300,
            ], [1, 11, 12])
        );

        return $this->titlesOf($options->getOptions());
    }

    /**
     * @param FilterOption[] $options
     *
     * @return list<string>
     */
    private function titlesOf(array $options): array
    {
        $titles = [];
        foreach ($options as $option) {
            $titles[] = $option->getTitle();
            $titles = [...$titles, ...$this->titlesOf($option->getChildren())];
        }

        return $titles;
    }
}
