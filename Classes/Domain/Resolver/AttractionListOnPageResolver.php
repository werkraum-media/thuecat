<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Resolver;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;

/**
 * Detection mechanism for the Search plugin
 * Looks up on the same page for any related list plugins
 *
 * If there are any hits (usually one), the configuration settings
 * for storagePid and recursive are taken from that list plugin.
 * Those are used to limit any records based filters to a set of values
 * that can be supplied by the list.
 * For example, the filter is town and a storage pid is applied, then only
 * records are listed that are in this folder and have town attached. Exactly this
 * list of towns is supplied to the search form. Other towns having records in other
 * folders are filtered out, because their selection would cause an emtpy result.
 *
 * Also, any editor-based filters that define the scope of the list
 * are taken and applied to the search demand, so it follows the same
 * restrictions.
 */
class AttractionListOnPageResolver
{
    /**
     * Editable on purpose — other list-bearing CEs may need detecting later.
     *
     * @var string[]
     */
    protected array $listContentTypes = [
        'werkraummedia_thuecatattractionlist',
        'werkraummedia_thuecatattractionlistfiltered',
    ];

    public function __construct(
        protected readonly FlexFormService $flexFormService,
        protected readonly TouristAttractionDemandFactory $demandFactory,
        protected readonly PageRepository $pageRepository,
    ) {
    }

    public function resolveForPage(ContentObjectRenderer $contentObject, int $pageId): ?ListPluginOnSamePage
    {
        $quotedTypes = implode(',', array_map(
            static fn (string $type): string => '\'' . $type . '\'',
            $this->listContentTypes
        ));

        // getRecords overlays workspace + language and applies FE restrictions.
        $rows = $contentObject->getRecords('tt_content', [
            'pidInList' => (string)$pageId,
            'where' => 'CType IN (' . $quotedTypes . ')',
            'orderBy' => 'sorting',
            'max' => 1,
        ]);

        $row = $rows[0] ?? null;
        if (!is_array($row)) {
            return null;
        }

        $flexForm = $row['pi_flexform'] ?? '';
        // @todo v13: FlexFormService is aliased to FlexFormTools in v14; switch when v13 support drops.
        $parsed = is_string($flexForm) && $flexForm !== ''
            ? $this->flexFormService->convertFlexFormContentToArray($flexForm)
            : [];
        $settings = $parsed['settings'] ?? [];
        $editorFilter = $this->demandFactory->fromSettings(is_array($settings) ? $settings : []);

        $pages = is_string($row['pages'] ?? null) ? $row['pages'] : '';
        $storagePageIds = GeneralUtility::intExplode(',', $pages, true);
        // Honour the list's `recursive` depth the same way the plugin would.
        $recursive = is_numeric($row['recursive'] ?? null) ? (int)$row['recursive'] : 0;
        if ($storagePageIds !== [] && $recursive > 0) {
            $storagePageIds = $this->pageRepository->getPageIdsRecursive($storagePageIds, $recursive);
        }

        return new ListPluginOnSamePage($editorFilter, $storagePageIds);
    }
}
