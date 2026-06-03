<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemandFactory;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TownRepository;
use WerkraumMedia\ThueCat\Pagination\PaginationFactory;

class TouristAttractionController extends ActionController
{
    public function __construct(
        protected TouristAttractionRepository $touristAttractionRepository,
        protected TownRepository $townRepository,
        protected TouristAttractionDemandFactory $demandFactory,
        protected PaginationFactory $paginationFactory,
    ) {
    }

    public function initializeListAction(): void
    {
        $this->allowDemandMapping();
    }

    public function initializeSearchFormAction(): void
    {
        $this->allowDemandMapping();
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

    public function listAction(?TouristAttractionDemand $demand = null, int $currentPage = 1): ResponseInterface
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');

        $demand ??= new TouristAttractionDemand();
        // Editor-locked filters override the visitor's input so a search stays
        // within the configured set.
        $editorFilter = $this->demandFactory->fromSettings($this->settings);
        $this->demandFactory->applyEditorFilter($demand, $editorFilter);

        $attractions = $this->touristAttractionRepository->findByDemand($demand);
        $pagination = $this->paginationFactory->fromSettings($attractions, $currentPage, $this->settings);

        $this->view->assignMultiple([
            'list' => $pagination,
            'demand' => $demand,
            'data' => $contentObject->data,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(TouristAttraction $attraction): ResponseInterface
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
        $editorFilter = $this->demandFactory->fromSettings($this->settings);

        $this->view->assignMultiple([
            'demand' => $demand,
            'towns' => $this->townRepository->findAllSortedByTitle(),
            // Locked filters render as hidden (kept in the submitted demand);
            // listAction re-forces them, so a tampered value can't widen the set.
            'lockedMap' => $editorFilter->getLockedMap(),
        ]);
        return $this->htmlResponse();
    }
}
