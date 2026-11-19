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

namespace WerkraumMedia\ThueCat\Tests\Functional\Removal;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileRemovalTest extends AbstractRemovalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Roots.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Relations.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Files.csv');

        // Storage 1 of the fixture's sys_file rows; it has no _recycler_ folder, so a deleted file is gone.
        $this->assertSame(1, $this->get(StorageRepository::class)->createLocalStorage('fileadmin', 'fileadmin/', 'relative'));
        GeneralUtility::mkdir_deep($this->instancePath . '/fileadmin/removal');
        foreach (['only-root', 'shared', 'translation', 'translation-media', 'inline-child', 'refused', 'event', 'relation'] as $name) {
            file_put_contents($this->instancePath . '/fileadmin/removal/' . $name . '.jpg', $name);
        }
    }

    #[Test]
    public function anImageOnlyTheRemovedRootUsedIsDeletedFromItsStorage(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100, true);

        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/only-root.jpg');
    }

    #[Test]
    public function anImageAKeptTrailUsesIsKept(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100, true);

        $this->assertFileExists($this->instancePath . '/fileadmin/removal/shared.jpg');
    }

    #[Test]
    public function everyImageOfTheRootsTranslationIsDeleted(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100, true);

        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/translation.jpg');
        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/translation-media.jpg');
    }

    #[Test]
    public function anImageOfTheRootsInlineChildIsDeleted(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100, true);

        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/inline-child.jpg');
    }

    #[Test]
    public function anEventsImageOnlyItUsedIsDeleted(): void
    {
        $this->removeRoot('tx_events_domain_model_event', 100, true);

        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/event.jpg');
    }

    #[Test]
    public function anImageOfARelationRemovedWithTheRootIsDeleted(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 105, true);

        $this->assertTrue($this->isDeleted('tx_thuecat_tourist_information', 100));
        $this->assertFileDoesNotExist($this->instancePath . '/fileadmin/removal/relation.jpg');
        $this->assertEquals(1, $this->jobRow($job)['removed_files']);
        $this->assertEquals(0, $this->jobRow($job)['errors']);
    }

    #[Test]
    public function withoutTheFileOptionNoImageIsDeleted(): void
    {
        $this->removeRoot('tx_thuecat_tourist_attraction', 100);
        $this->removeRoot('tx_thuecat_tourist_attraction', 105);

        $this->assertFileExists($this->instancePath . '/fileadmin/removal/only-root.jpg');
        $this->assertFileExists($this->instancePath . '/fileadmin/removal/relation.jpg');
    }

    #[Test]
    public function theJobCountsTheRemovedFiles(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 100, true);

        $this->assertEquals(4, $this->jobRow($job)['removed_files']);
        $this->assertEquals(0, $this->jobRow($job)['errors']);
    }

    #[Test]
    public function aRefusedFileDeletionIsLoggedOnTheJob(): void
    {
        $backendUser = $GLOBALS['BE_USER'];
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 104, true, 3);

        $this->assertSame($backendUser, $GLOBALS['BE_USER']);
        $this->assertSame($request, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->assertTrue($this->isDeleted('tx_thuecat_tourist_attraction', 104));
        $this->assertFileExists($this->instancePath . '/fileadmin/removal/refused.jpg');
        $this->assertEquals(0, $this->jobRow($job)['removed_files']);
        $this->assertEquals(1, $this->jobRow($job)['errors']);
        $errorLog = $this->jobRow($job)['error_log'];
        $this->assertIsString($errorLog);
        $this->assertStringContainsString('sys_file:5', $errorLog);
    }

    #[Test]
    public function aRefusedFileDeletionIsLoggedInTheSystemLogUnderTheJobsUser(): void
    {
        $job = $this->removeRoot('tx_thuecat_tourist_attraction', 104, true, 3);

        $errors = $this->systemLogErrors(3);
        $this->assertCount(1, $errors);
        $this->assertSame(SystemLogType::ERROR, $errors[0]['type']);
        $this->assertStringContainsString('Removal job ' . $job . ':', $errors[0]['details']);
        $this->assertStringContainsString('sys_file:5', $errors[0]['details']);
    }
}
