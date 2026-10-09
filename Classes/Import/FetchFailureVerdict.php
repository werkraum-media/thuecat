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

namespace WerkraumMedia\ThueCat\Import;

use Throwable;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\InvalidResponseException;
use WerkraumMedia\ThueCat\Import\Importer\FetchData\ResourceNotFoundException;

/**
 * Decides whether a failed fetch may cost a stored relation.
 *
 * Only upstream positively reporting a resource absent (404, 410) counts as a
 * withdrawal. Every other failure — bad credential, rate limit, server error,
 * transport fault — is transient and keeps what is stored, because such faults
 * arrive for every resource on a host at once and would strip a whole run's
 * relations from a single fault.
 *
 * Callers reach for this wherever a relation set is submitted as a whole, since
 * submitting the set removes whatever is missing from it. A caller that writes a
 * scalar foreign key needs no verdict: a failed fetch leaves the stored value
 * untouched.
 */
class FetchFailureVerdict
{
    protected const GONE_STATUSES = [404, 410];

    public function statusMeansGone(?int $status): bool
    {
        return $status !== null && in_array($status, self::GONE_STATUSES, true);
    }

    public function failureMeansGone(Throwable $failure): bool
    {
        if ($failure instanceof ResourceNotFoundException) {
            return true;
        }

        if (!$failure instanceof InvalidResponseException) {
            return false;
        }

        return $this->statusMeansGone($this->statusFromMessage($failure->getMessage()));
    }

    // Non-200s without a dedicated exception class carry the status in the
    // message only.
    protected function statusFromMessage(string $message): ?int
    {
        if (preg_match('/failed with status (\d{3})/', $message, $matches) !== 1) {
            return null;
        }

        return (int)$matches[1];
    }
}
