<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
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
    private const GONE_STATUSES = [404, 410];

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
    private function statusFromMessage(string $message): ?int
    {
        if (preg_match('/failed with status (\d{3})/', $message, $matches) !== 1) {
            return null;
        }

        return (int)$matches[1];
    }
}
