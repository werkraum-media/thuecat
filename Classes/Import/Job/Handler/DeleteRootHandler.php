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

namespace WerkraumMedia\ThueCat\Import\Job\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\ImportJob;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItem;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\DeleteRoot;
use WerkraumMedia\ThueCat\Import\SyncChannel\SyncChannelReaper;
use WerkraumMedia\ThueCat\Service\SitePageIds;

/**
 * Deletes what the channel listed as removed or expired. How the job's imports went has no bearing on it: upstream
 * issued the removal.
 */
#[AsMessageHandler]
final readonly class DeleteRootHandler
{
    public function __construct(
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportConfigurationRepository $configurationRepository,
        private SiteFinder $siteFinder,
        private SitePageIds $sitePageIds,
        private SyncChannelReaper $syncChannelReaper,
        private ImportLogger $importLogger,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(DeleteRoot $message): void
    {
        // The consumer is long-lived and the logger shared: entries of an earlier message must not reach this log.
        $this->importLogger->reset();
        $item = $this->importJobItems->begin($message->itemUid);
        $job = $this->importJobs->find($message->jobUid);
        if (!$item instanceof ImportJobItem || !$job instanceof ImportJob) {
            return;
        }

        $configuration = $this->configurationRepository->findOneByUid($job->configurationUid);
        if (!$configuration instanceof ImportConfiguration) {
            return;
        }

        // Sent first, so a failure of this item does not end the chain.
        $this->messageBus->dispatch($this->importJobItems->nextMessage($job->uid, $item->position));
        $site = $this->siteFinder->getSiteByPageId($configuration->getStoragePid());
        $this->importLogger->recordDataHandlerErrors(
            $this->syncChannelReaper->delete($item->target, $this->sitePageIds->forRootPage($site->getRootPageId())),
            0
        );
        $this->importLogger->writeLogInto($job->importLog, [], []);

        $this->importJobItems->markDone($item);
    }
}
