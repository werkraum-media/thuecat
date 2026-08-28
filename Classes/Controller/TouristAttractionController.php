<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Category;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\FilterOptions;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TownRepository;
use WerkraumMedia\ThueCat\Extension;
use WerkraumMedia\ThueCat\Frontend\Cache\CacheIdentifierFactory;
use WerkraumMedia\ThueCat\Frontend\Cache\CacheTagCollector;
use WerkraumMedia\ThueCat\Pagination\PaginationFactory;
use WerkraumMedia\ThueCat\Service\SearchFilterOptionsService;
use WerkraumMedia\ThueCat\Service\SiblingListPluginContext;
use WerkraumMedia\ThueCat\Service\SiblingListPluginLocator;

/**
 * v13 types $view as a union, v14 as ViewInterface. Narrowing it here keeps
 * render() a string on both.
 *
 * @property ViewInterface $view
 */
class TouristAttractionController extends AbstractActionController
{
    /** The record kind this controller serves. */
    protected const RECORD_TABLE = 'tx_thuecat_tourist_attraction';

    public function __construct(
        protected TouristAttractionRepository $touristAttractionRepository,
        protected TownRepository $townRepository,
        protected TouristAttractionDemandFactory $demandFactory,
        protected PaginationFactory $paginationFactory,
        protected ExtensionService $extensionService,
        protected SiblingListPluginLocator $siblingListPluginLocator,
        protected CacheManager $cacheManager,
        protected CacheIdentifierFactory $cacheIdentifierFactory,
        protected CacheTagCollector $cacheTagCollector,
        protected SearchFilterOptionsService $filterOptionsService,
    ) {
    }

    public function initializeListAction(): void
    {
        $this->allowDemandMapping();
    }

    public function initializeSearchFormAction(): void
    {
        $this->liftListDemandToUseInSearch();
        $this->allowDemandMapping();
    }

    public function initializeView(): void
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $this->view->assign('data', $contentObject->data);
    }

    public function listAction(?TouristAttractionDemand $demand = null, int $currentPage = 1): ResponseInterface
    {
        $demand = $this->buildDemandFromInputAndEditorSettings($demand);

        $this->redirectPostToGet($demand);

        $cache = $this->cacheManager->getCache(Extension::CACHE_LIST);
        $identifier = $this->cacheIdentifierFactory->forList(
            $this->pluginUid(),
            $demand,
            $currentPage,
            $this->languageId()
        );

        $cached = $cache->get($identifier);
        if (is_string($cached)) {
            return $this->htmlResponse($cached);
        }

        $attractions = $this->touristAttractionRepository->findByDemand($demand);
        $pagination = $this->paginationFactory->fromSettings($attractions, $currentPage, $this->settings);
        // Materialised once: needed for rendering and for tagging.
        $paginatedItems = $pagination->getPaginatedItems();
        $displayedRecords = is_array($paginatedItems)
            ? array_values($paginatedItems)
            : iterator_to_array($paginatedItems, false);

        $this->view->assignMultiple([
            'list' => $pagination,
            'items' => $this->renderItems($displayedRecords, 'thuecat_attraction_show'),
            'demand' => $demand,
        ]);
        $html = $this->view->render();

        // One tag per displayed record; an empty result falls back to the table.
        $cache->set($identifier, $html, $this->cacheTagCollector->forRecords(
            $displayedRecords,
            $this->cacheTagCollector->tableForModel(TouristAttraction::class)
        ));

        return $this->htmlResponse($html);
    }

    protected function pluginUid(): int
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        if (!$contentObject instanceof ContentObjectRenderer) {
            return 0;
        }
        $uid = $contentObject->data['uid'] ?? null;

        return is_scalar($uid) ? (int)$uid : 0;
    }

    public function showAction(?TouristAttraction $attraction = null): ResponseInterface
    {
        if ($attraction instanceof TouristAttraction) {
            $this->metaInformationService->setObject($attraction);
        }

        $this->view->assign('attraction', $attraction);
        return $this->htmlResponse();
    }

    /**
     * Renders a fixed, editor-curated set of attractions in the picked order.
     * Backend-only selection; no demand, no filtering, no pagination.
     */
    public function selectedListAction(): ResponseInterface
    {
        $selectedRecordsSetting = $this->settings['selectedRecords'] ?? '';
        $uids = is_string($selectedRecordsSetting)
            ? GeneralUtility::intExplode(',', $selectedRecordsSetting, true)
            : [];

        $this->view->assign(
            'items',
            $this->renderItems($this->touristAttractionRepository->findBySelectedRecords($uids), 'thuecat_attraction_show')
        );
        return $this->htmlResponse();
    }

    public function searchFormAction(?TouristAttractionDemand $demand = null): ResponseInterface
    {
        $demand ??= new TouristAttractionDemand();

        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $routing = $this->request->getAttribute('routing');
        $pageId = $routing instanceof PageArguments ? $routing->getPageId() : 0;

        $cache = $this->cacheManager->getCache(Extension::CACHE_SEARCH_MASK);
        // Keyed on the demand as sent: the sibling lookup below mutates it, and
        // resolving that sibling is the work a hit skips.
        $identifier = $this->cacheIdentifierFactory->forSearchMask(
            $this->pluginUid(),
            $pageId,
            $demand,
            $this->languageId()
        );

        $cached = $cache->get($identifier);
        if (is_string($cached)) {
            return $this->htmlResponse($cached);
        }

        $listPluginOnSamePage = $this->detectSiblingListAndApplyTheirFilters($contentObject, $pageId, $demand);
        $formTargetPid = $this->determineSearchActionTargetPid($listPluginOnSamePage, $pageId);
        $filterOptions = $this->filterOptions($listPluginOnSamePage);

        $this->view->assignMultiple([
            'demand' => $demand,
            ...$filterOptions,
            // pre-selected filters render hidden; listAction re-forces them so a tampered value can't widen.
            'lockedMap' => $listPluginOnSamePage?->getEditorFilter()->getLockedMap() ?? [],
            'formTargetPid' => $formTargetPid,
        ]);
        $html = $this->view->render();

        // Table-level: the mask depends on the whole set, and its options are
        // DTOs carrying no table of their own, so the tables are named here.
        $cache->set($identifier, $html, $this->cacheTagCollector->forRecordSets([
            $this->cacheTagCollector->tableForModel(Town::class),
            $this->cacheTagCollector->tableForModel(Category::class),
        ]));

        return $this->htmlResponse($html);
    }

    /**
     * After a search the demand travels in the list namespace; lift it into //
     * search action argument so Extbase maps it back and the form shows the visitor's input.
     */
    protected function liftListDemandToUseInSearch(): void
    {
        $listNamespace = $this->extensionService->getPluginNamespace('ThueCat', 'TouristAttractionList');

        $routing = $this->request->getAttribute('routing');
        $listArguments = $routing instanceof PageArguments ? $routing->get($listNamespace) : null;
        $listArguments = is_array($listArguments) ? $listArguments : [];
        $parsedBody = $this->request->getParsedBody();
        if ($this->request->getMethod() === 'POST' && is_array($parsedBody)) {
            $body = $parsedBody[$listNamespace] ?? [];
            $listArguments = array_replace_recursive($listArguments, is_array($body) ? $body : []);
        }

        if (!isset($listArguments['demand']) || !is_array($listArguments['demand'])) {
            return;
        }

        $extbaseParameters = $this->request->getAttribute('extbase');
        if ($extbaseParameters instanceof ExtbaseRequestParameters) {
            $extbaseParameters->setArgument('demand', $listArguments['demand']);
        }
    }

    /**
     * Demand is a trusted-shape DTO (typed setters only); allow request mapping
     * of all its properties so new filters need no change here.
     */
    protected function allowDemandMapping(): void
    {
        if (!$this->arguments->hasArgument('demand')) {
            return;
        }
        $this->arguments->getArgument('demand')
            ->getPropertyMappingConfiguration()
            ->allowAllProperties()
        ;
    }

    /**
     * Turn a posted search form into a bookmarkable GET URL carrying demand values
     */
    protected function redirectPostToGet(TouristAttractionDemand $demand): void
    {
        if ($this->request->getMethod() !== 'POST') {
            return;
        }

        $parameters = $demand->getQueryParameters();
        $parameter = $parameters === [] ? [] : ['demand' => $parameters];
        $namespace = $this->extensionService->getPluginNamespace('ThueCat', 'TouristAttractionList');

        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        throw new PropagateResponseException(
            $this->redirectToUri($contentObject->typoLink_URL([
                'parameter' => 't3://page?uid=current',
                'additionalParams' => '&' . http_build_query([$namespace => $parameter]),
            ]))
        );
    }

    /**
     * if sibling list CE on the same page carries any pre-selection, apply them to the demand object
     * List and Search both will have the same selected values, and visitors can not widen the search
     * scope by manipulating hidden fields (they are overridden here again).
     */
    protected function buildDemandFromInputAndEditorSettings(?TouristAttractionDemand $demand = null): TouristAttractionDemand
    {
        $demand ??= new TouristAttractionDemand();
        $editorFilter = $this->demandFactory->fromSettings($this->settings);
        $this->demandFactory->applyEditorFilter($demand, $editorFilter);
        return $demand;
    }

    /**
     * apply the filters from the list plugin to the demand object
     */
    protected function detectSiblingListAndApplyTheirFilters(ContentObjectRenderer $contentObject, int $pageId, TouristAttractionDemand $demand): ?SiblingListPluginContext
    {
        $listPluginOnSamePage = $this->siblingListPluginLocator->resolveForPage($contentObject, $pageId);

        if ($listPluginOnSamePage !== null) {
            $this->demandFactory->applyEditorFilter($demand, $listPluginOnSamePage->getEditorFilter());
        }
        return $listPluginOnSamePage;
    }

    /**
     * On a list page post to self; otherwise to the configured central search page.
     *
     * @param SiblingListPluginContext|null $listPluginOnSamePage
     * @param int $pageId
     *
     * @return int|mixed|null
     */
    protected function determineSearchActionTargetPid(?SiblingListPluginContext $listPluginOnSamePage, int $pageId): mixed
    {
        $pageSettings = $this->settings['page'] ?? [];
        $pidSettings = is_array($pageSettings) ? ($pageSettings['pid'] ?? []) : [];
        $centralPid = is_array($pidSettings) ? ($pidSettings['thuecat_attraction_search'] ?? null) : null;
        $formTargetPid = $listPluginOnSamePage !== null ? $pageId : $centralPid;
        return $formTargetPid;
    }

    /**
     * What every filter of the mask offers, keyed by the name its template
     * binds to. The sibling list narrows the scope; this controller names the
     * record kind it searches.
     *
     * @return array<string, FilterOptions>
     */
    protected function filterOptions(?SiblingListPluginContext $listPluginOnSamePage): array
    {
        return $this->filterOptionsService->build(
            $this->request,
            self::RECORD_TABLE,
            $listPluginOnSamePage?->getStoragePageIds() ?? [],
            $listPluginOnSamePage?->getEditorFilter()
        );
    }
}
