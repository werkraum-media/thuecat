<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Trail;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TrailRepository;

class TrailController extends AbstractActionController
{
    public function __construct(protected TrailRepository $trailRepository)
    {
    }

    public function initializeView(): void
    {
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = $this->request->getAttribute('currentContentObject');
        $this->view->assign('data', $contentObject->data);
    }

    public function showAction(?Trail $trail = null): ResponseInterface
    {
        if ($trail instanceof Trail) {
            $this->metaInformationService->setObject($trail);
        }

        $this->view->assign('trail', $trail);
        return $this->htmlResponse();
    }

    /**
     * Renders a fixed, editor-curated set of trails in the picked order.
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
            $this->renderItems($this->trailRepository->findBySelectedRecords($uids), 'thuecat_trail_show')
        );
        return $this->htmlResponse();
    }
}
