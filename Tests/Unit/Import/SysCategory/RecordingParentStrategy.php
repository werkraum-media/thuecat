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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\SysCategory;

use WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategy;
use WerkraumMedia\ThueCat\Import\Vocabulary\VocabularyIndex;

/**
 * Takes the first candidate and records what it was asked, so a test can assert
 * the builder consults it for a genuine branch and not for a restated chain.
 */
final class RecordingParentStrategy implements ParentStrategy
{
    /** @var list<array{class: string, candidates: list<string>}> */
    public array $asked = [];

    public function choose(VocabularyIndex $index, string $class, array $candidates): string
    {
        $this->asked[] = ['class' => $class, 'candidates' => $candidates];

        return $candidates[0];
    }

    public function name(): string
    {
        return 'first';
    }
}
