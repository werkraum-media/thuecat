<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Town;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TownRepository;
use WerkraumMedia\ThueCat\Domain\Resolver\AttractionListOnPageResolver;
use WerkraumMedia\ThueCat\Domain\Resolver\ListPluginOnSamePage;
use WerkraumMedia\ThueCat\Pagination\PaginationFactory;

class TouristAttractionController extends ActionController
{
    public function __construct(
        protected TouristAttractionRepository $touristAttractionRepository,
        protected TownRepository $townRepository,
        protected TouristAttractionDemandFactory $demandFactory,
        protected PaginationFactory $paginationFactory,
        protected ExtensionService $extensionService,
        protected AttractionListOnPageResolver $attractionListOnPageResolver,
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

        $attractions = $this->touristAttractionRepository->findByDemand($demand);
        $pagination = $this->paginationFactory->fromSettings($attractions, $currentPage, $this->settings);

        $this->view->assignMultiple([
            'list' => $pagination,
            'demand' => $demand,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(?TouristAttraction $attraction = null): ResponseInterface
    {
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

        $this->view->assignMultiple([
            'attractions' => $this->touristAttractionRepository->findBySelectedRecords($uids),
        ]);
        return $this->htmlResponse();
    }

    public function searchFormAction(?TouristAttractionDemand $demand = null): ResponseInterface
    {
        $demand ??= new TouristAttractionDemand();

        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $routing = $this->request->getAttribute('routing');
        $pageId = $routing instanceof PageArguments ? $routing->getPageId() : 0;
        $listPluginOnSamePage = $this->detectSiblingListAndApplyTheirFilters($contentObject, $pageId, $demand);
        $formTargetPid = $this->determineSearchActionTargetPid($listPluginOnSamePage, $pageId);
        // @todo Any future record-backed filter option needs the same storage scoping.
        $towns = $this->adjustFilterTownValuesToGivenStoragePid($listPluginOnSamePage);

        $this->view->assignMultiple([
            'demand' => $demand,
            'towns' => $towns,
            // pre-selected filters render hidden; listAction re-forces them so a tampered value can't widen.
            'lockedMap' => $listPluginOnSamePage?->getEditorFilter()->getLockedMap() ?? [],
            'formTargetPid' => $formTargetPid,
        ]);
        return $this->htmlResponse();
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
    protected function detectSiblingListAndApplyTheirFilters(ContentObjectRenderer $contentObject, int $pageId, TouristAttractionDemand $demand): ?ListPluginOnSamePage
    {
        $listPluginOnSamePage = $this->attractionListOnPageResolver->resolveForPage($contentObject, $pageId);

        if ($listPluginOnSamePage !== null) {
            $this->demandFactory->applyEditorFilter($demand, $listPluginOnSamePage->getEditorFilter());
        }
        return $listPluginOnSamePage;
    }

    /**
     * On a list page post to self; otherwise to the configured central search page.
     *
     * @param ListPluginOnSamePage|null $listPluginOnSamePage
     * @param int $pageId
     *
     * @return int|mixed|null
     */
    protected function determineSearchActionTargetPid(?ListPluginOnSamePage $listPluginOnSamePage, int $pageId): mixed
    {
        $pageSettings = $this->settings['page'] ?? [];
        $pidSettings = is_array($pageSettings) ? ($pageSettings['pid'] ?? []) : [];
        $centralPid = is_array($pidSettings) ? ($pidSettings['thuecat_attraction_search'] ?? null) : null;
        $formTargetPid = $listPluginOnSamePage !== null ? $pageId : $centralPid;
        return $formTargetPid;
    }

    /**
     * Offer only towns the list on this page can actually return; all towns otherwise.
     *
     * @param ListPluginOnSamePage|null $listPluginOnSamePage
     *
     * @return array<Town>|QueryResultInterface<Town>
     */
    public function adjustFilterTownValuesToGivenStoragePid(?ListPluginOnSamePage $listPluginOnSamePage): array|QueryResultInterface
    {
        $storagePageIds = $listPluginOnSamePage?->getStoragePageIds() ?? [];
        return $storagePageIds === []
            ? $this->townRepository->findAllForSearchFormSortedByTitle()
            : $this->touristAttractionRepository->findTownsInStorageSortedByTitle($storagePageIds);
    }
}
