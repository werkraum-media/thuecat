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

namespace WerkraumMedia\ThueCat\Removal;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Deletes one file through FAL, with the rights of the user who started the job.
 */
class FileRemoval
{
    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return ?string why the file could not be deleted
     */
    public function remove(int $fileUid, BackendUserAuthentication $backendUser): ?string
    {
        try {
            $file = $this->resourceFactory->getFileObject($fileUid);
            $this->storageOf($file->getStorage(), $backendUser)->deleteFile($file);
        } catch (Exception $exception) {
            return 'sys_file:' . $fileUid . ' ' . $exception->getMessage();
        }

        return null;
    }

    /**
     * Core grants a user's file permissions and file mounts to a storage only in a backend request; the worker
     * is none. Core's own listener therefore runs once more, for a copy of the storage and with the job's user,
     * so the shared storage keeps its rights.
     */
    protected function storageOf(ResourceStorage $storage, BackendUserAuthentication $backendUser): ResourceStorage
    {
        $userStorage = clone $storage;
        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $previousRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        ;
        try {
            $this->eventDispatcher->dispatch(new AfterResourceStorageInitializationEvent($userStorage));
        } finally {
            $GLOBALS['BE_USER'] = $previousBackendUser;
            $GLOBALS['TYPO3_REQUEST'] = $previousRequest;
        }

        return $userStorage;
    }
}
