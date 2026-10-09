<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace WerkraumMedia\ThueCat\Domain\Model\Backend;

use DateTimeImmutable;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as Typo3AbstractEntity;

class AbstractEntity extends Typo3AbstractEntity
{
    protected string $remoteId = '';

    protected string $title = '';

    protected string $description = '';

    protected ?DateTimeImmutable $tstamp = null;

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLastImported(): ?DateTimeImmutable
    {
        return $this->tstamp;
    }
}
