<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;

class TouristAttractionController extends ActionController
{
    public function __construct(
        protected TouristAttractionRepository $touristAttractionRepository,
        protected readonly PageRepository $pageRepository
    )
    {
    }

    public function listAction(): ResponseInterface
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $dataFromTypoScript = $contentObject->data;

        $attractions = $this->touristAttractionRepository->findAll();
        $this->view->assignMultiple(['attractions' => $attractions, 'data' => $dataFromTypoScript]);
        return $this->htmlResponse($this->view->render());
    }

    public function showAction(TouristAttraction $attraction): ResponseInterface
    {
        $this->view->assign('attraction', $attraction);
        return $this->htmlResponse($this->view->render());
    }

}