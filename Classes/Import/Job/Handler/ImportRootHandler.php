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
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\ImportLogger;
use WerkraumMedia\ThueCat\Import\Job\ImportConfigurationGoneException;
use WerkraumMedia\ThueCat\Import\Job\ImportJob;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItem;
use WerkraumMedia\ThueCat\Import\Job\ImportJobItems;
use WerkraumMedia\ThueCat\Import\Job\ImportJobs;
use WerkraumMedia\ThueCat\Import\Job\Message\ImportRoot;
use WerkraumMedia\ThueCat\Import\Watchdog\MessageDeadline;

#[AsMessageHandler]
final readonly class ImportRootHandler
{
    public function __construct(
        private ImportJobs $importJobs,
        private ImportJobItems $importJobItems,
        private ImportConfigurationRepository $configurationRepository,
        private Importer $importer,
        private ImportLogger $importLogger,
        private MessageDeadline $deadline,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ImportRoot $message): void
    {
        // The consumer is long-lived and both are shared: what an earlier message left must not reach this one.
        $this->importLogger->reset();
        $this->deadline->reset();
        $job = $this->importJobs->find($message->jobUid);
        if (!$job instanceof ImportJob) {
            return;
        }

        $item = $this->importJobItems->begin($message->itemUid);
        if (!$item instanceof ImportJobItem) {
            return;
        }

        // Sent first, so a failure of this item does not end the chain.
        if ($this->importJobItems->claimSuccessor($item)) {
            $this->messageBus->dispatch($this->importJobItems->nextMessage($job->uid, $item->position));
        }

        $configuration = $this->configurationRepository->findOneByUid($job->configurationUid);
        if (!$configuration instanceof ImportConfiguration) {
            throw new ImportConfigurationGoneException(
                sprintf('Configuration %d of import job %d is gone.', $job->configurationUid, $job->uid),
                1795584926
            );
        }

        $outcome = $this->importer->importRoot($configuration, $item->target, $job->uid, $item->uid, $job->bypassCache, $job->skipMedia);
        $this->importLogger->writeLogInto($job->importLog, $outcome->loggerPayload, $outcome->substNEWwithIDs);
        $this->importJobItems->markDone($item);
    }
}
