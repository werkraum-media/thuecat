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

namespace WerkraumMedia\ThueCat\Domain\Repository\Frontend;

use TYPO3\CMS\Extbase\Persistence\Repository;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;

class AbstractThuecatObjectRepository extends Repository
{
    /**
     * Returns the given records in the exact order of the passed uids.
     *
     * The uids are default-language uids. findByUid() overlays each one for
     * the request's language; a uid-matching query cannot, as its language
     * constraint rejects the default-language row and the uid test rejects
     * the translation.
     *
     * @param int[] $uids
     *
     * @return Base[]
     */
    public function findBySelectedRecords(array $uids): array
    {
        $records = [];
        foreach ($uids as $uid) {
            $record = $this->findByUid($uid);
            if ($record instanceof Base) {
                $records[] = $record;
            }
        }

        return $records;
    }
}
