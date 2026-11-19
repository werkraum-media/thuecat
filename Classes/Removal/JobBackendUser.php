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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides the backend user who started a job. The worker runs as _cli_, an admin; deleting and logging as the
 * job's own user brings back the group's permissions and puts the job's entries under that user in the log.
 */
final readonly class JobBackendUser
{
    public function of(RemovalJob $job): BackendUserAuthentication
    {
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->setBeUserByUid($job->backendUser);
        $backendUser->fetchGroupData();

        return $backendUser;
    }
}
