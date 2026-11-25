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

namespace WerkraumMedia\ThueCat\Tests\Functional\Import\Job;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Import\MediaStagingSweep;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

final class MediaStagingSweepTest extends AbstractFunctionalTestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = $this->createFileStorage();
        GeneralUtility::mkdir($this->folder . '/other');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Import/StagingSweep.csv');
    }

    #[Test]
    public function foldersOfJobsThatAreNotOpenAreRemovedFromEveryConfigurationsFolder(): void
    {
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_job1_item1');
        GeneralUtility::mkdir($this->folder . '/other/_thuecat_import_job2_item3');
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_job99_item1');

        $this->get(MediaStagingSweep::class)->sweep();

        $this->assertSame([], [...$this->stagingFoldersIn($this->folder), ...$this->stagingFoldersIn($this->folder . '/other')]);
    }

    #[Test]
    public function anOpenJobsFolderIsKept(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->insert('tx_thuecat_import_job', ['uid' => 3, 'configuration' => 1, 'status' => 'running'])
        ;
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_job3_item1');
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_job1_item1');

        $this->get(MediaStagingSweep::class)->sweep();

        $this->assertSame([$this->folder . '/_thuecat_import_job3_item1'], $this->stagingFoldersIn($this->folder));
    }

    #[Test]
    public function foldersOfThuecat7AreRemovedWhileNoJobIsOpen(): void
    {
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_6578a1b2c3d4e');

        $this->get(MediaStagingSweep::class)->sweep();

        $this->assertSame([], $this->stagingFoldersIn($this->folder));
    }

    // Their names carry no job, so any open job may be the one still using one.
    #[Test]
    public function foldersOfThuecat7AreKeptWhileAJobIsOpen(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_thuecat_import_job')
            ->insert('tx_thuecat_import_job', ['uid' => 3, 'configuration' => 1, 'status' => 'running'])
        ;
        GeneralUtility::mkdir($this->folder . '/_thuecat_import_6578a1b2c3d4e');

        $this->get(MediaStagingSweep::class)->sweep();

        $this->assertSame([$this->folder . '/_thuecat_import_6578a1b2c3d4e'], $this->stagingFoldersIn($this->folder));
    }
}
