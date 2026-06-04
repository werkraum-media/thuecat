<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TownRepository;
use WerkraumMedia\ThueCat\Domain\Resolver\AttractionListOnPageResolver;
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
        $this->adoptListDemandForRepopulation();
        $this->allowDemandMapping();
    }

    // After a search the demand travels in the list namespace; lift it into our
    // own argument so Extbase maps it back and the form shows the visitor's input.
    protected function adoptListDemandForRepopulation(): void
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

    // Demand is a trusted-shape DTO (typed setters only); allow request mapping
    // of all its properties so new filters need no change here.
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
     * Turn a posted search form into a bookmarkable GET URL carrying only the
     * demand values (cHash-excluded), so the form's referrer/trusted-properties
     * fields never reach the cacheable URL.
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
            ])),
            303
        );
    }

    public function listAction(?TouristAttractionDemand $demand = null, int $currentPage = 1): ResponseInterface
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');

        $demand ??= new TouristAttractionDemand();
        // Editor-locked filters override the visitor's input so a search stays
        // within the configured set.
        $editorFilter = $this->demandFactory->fromSettings($this->settings);
        $this->demandFactory->applyEditorFilter($demand, $editorFilter);

        // A posted search form redirects here to a clean, bookmarkable GET URL.
        $this->redirectPostToGet($demand);

        $attractions = $this->touristAttractionRepository->findByDemand($demand);
        $pagination = $this->paginationFactory->fromSettings($attractions, $currentPage, $this->settings);

        $this->view->assignMultiple([
            'list' => $pagination,
            'demand' => $demand,
            'data' => $contentObject->data,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(?TouristAttraction $attraction = null): ResponseInterface
    {
        // No/invalid attraction (e.g. plugin reached without parameters): render empty.
        $this->view->assign('attraction', $attraction);
        return $this->htmlResponse();
    }

    /**
     * Renders a fixed, editor-curated set of attractions in the picked order.
     * Backend-only selection; no demand, no filtering, no pagination.
     */
    public function selectedListAction(): ResponseInterface
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $dataFromTypoScript = $contentObject->data;

        $selectedRecordsSetting = $this->settings['selectedRecords'] ?? '';
        $uids = is_string($selectedRecordsSetting)
            ? GeneralUtility::intExplode(',', $selectedRecordsSetting, true)
            : [];

        $this->view->assignMultiple([
            'attractions' => $this->touristAttractionRepository->findBySelectedRecords($uids),
            'data' => $dataFromTypoScript,
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
        // A list CE on this page makes the form stay here and supplies the locks.
        $resolvedList = $this->attractionListOnPageResolver->resolveForPage($contentObject, $pageId);

        // Force locked fields to the preset so hidden inputs carry the editor value.
        if ($resolvedList !== null) {
            $this->demandFactory->applyEditorFilter($demand, $resolvedList->getEditorFilter());
        }

        // On a list page post to self; otherwise to the configured central search page.
        $pageSettings = $this->settings['page'] ?? [];
        $pidSettings = is_array($pageSettings) ? ($pageSettings['pid'] ?? []) : [];
        $centralPid = is_array($pidSettings) ? ($pidSettings['thuecat_attraction_search'] ?? null) : null;
        $formTargetPid = $resolvedList !== null ? $pageId : $centralPid;

        // Offer only towns the list on this page can actually return; all towns otherwise.
        // @todo Any future record-backed filter option needs the same storage scoping.
        $storagePageIds = $resolvedList?->getStoragePageIds() ?? [];
        $towns = $storagePageIds === []
            ? $this->townRepository->findAllForSearchFormSortedByTitle()
            : $this->touristAttractionRepository->findTownsInStorageSortedByTitle($storagePageIds);

        $this->view->assignMultiple([
            'demand' => $demand,
            'towns' => $towns,
            // Locked filters render hidden; listAction re-forces them so a tampered value can't widen.
            'lockedMap' => $resolvedList?->getEditorFilter()->getLockedMap() ?? [],
            'formTargetPid' => $formTargetPid,
        ]);
        return $this->htmlResponse();
    }
}
