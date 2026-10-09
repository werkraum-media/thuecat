<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Trail;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TrailRepository;

class TrailController extends AbstractActionController
{
    /** The record kind this controller serves. */
    protected const RECORD_TABLE = 'tx_thuecat_trail';

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
        if ($this->selectedRecordUid() > 0) {
            $selected = $this->selectedRecord($this->trailRepository);
            $trail = $selected instanceof Trail ? $selected : null;
        }

        if ($trail instanceof Trail) {
            $this->metaInformationService->setObject($trail);
        } else {
            $this->addCacheTagForEmptyDetailView(self::RECORD_TABLE);
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
