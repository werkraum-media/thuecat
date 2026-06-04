<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Resolver;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;

/**
 * null = no list on page; otherwise the list's preset + storage pages.
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

    public function resolveForPage(ContentObjectRenderer $contentObject, int $pageId): ?ResolvedList
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

        return new ResolvedList($editorFilter, $storagePageIds);
    }
}
