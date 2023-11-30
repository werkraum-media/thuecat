<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Model\Backend;

/*
 * Copyright (C) 2021 Daniel Siepmann <coding@daniel-siepmann.de>
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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase as TestCase;
use WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration;

class ImportConfigurationTest extends TestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $subject = new ImportConfiguration();

        self::assertInstanceOf(ImportConfiguration::class, $subject);
    }

    #[Test]
    public function returnsTitle(): void
    {
        $subject = new ImportConfiguration();
        $subject->_setProperty('title', 'Example Title');

        self::assertSame('Example Title', $subject->getTitle());
    }

    #[Test]
    public function returnsType(): void
    {
        $subject = new ImportConfiguration();
        $subject->_setProperty('type', 'static');

        self::assertSame('static', $subject->getType());
    }

    #[Test]
    public function returnsTableName(): void
    {
        $subject = new ImportConfiguration();

        self::assertSame('tx_thuecat_import_configuration', $subject->getTableName());
    }

    #[Test]
    public function returnsLastChanged(): void
    {
        $lastChanged = new DateTimeImmutable();

        $subject = new ImportConfiguration();

        $subject->_setProperty('tstamp', $lastChanged);

        self::assertSame($lastChanged, $subject->getLastChanged());
    }

    #[Test]
    public function returnsStoragePidWhenSet(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="storagePid">',
            '<value index="vDEF">20</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame(20, $subject->getStoragePid());
    }

    #[Test]
    public function returnsZeroAsStoragePidWhenNoConfigurationExists(): void
    {
        $flexForm = '';

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame(0, $subject->getStoragePid());
    }

    #[Test]
    public function returnsZeroAsStoragePidWhenNegativePidIsConfigured(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="storagePid">',
            '<value index="vDEF">-1</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame(0, $subject->getStoragePid());
    }

    #[Test]
    public function returnsZeroAsStoragePidWhenNoneNumericPidIsConfigured(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="storagePid">',
            '<value index="vDEF">abc</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame(0, $subject->getStoragePid());
    }

    #[Test]
    public function returnsUrlsWhenSet(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="urls">',
            '<el index="el">',
            '<field index="6098e0b6d3fff074555176">',
            '<value index="url">',
            '<el>',
            '<field index="url">',
            '<value index="vDEF">https://thuecat.org/resources/942302009360-jopp</value>',
            '</field>',
            '</el>',
            '</value>',
            '<value index="_TOGGLE">0</value>',
            '</field>',
            '</el>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame([
            'https://thuecat.org/resources/942302009360-jopp',
        ], $subject->getUrls());
    }

    #[Test]
    public function returnsEmptyArrayAsUrlsWhenNoConfigurationExists(): void
    {
        $flexForm = '';

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame([], $subject->getUrls());
    }

    #[Test]
    public function returnsEmptyArrayAsUrlsWhenNoUrlsAreConfigured(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="storagePid">',
            '<value index="vDEF">10</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame([], $subject->getUrls());
    }

    #[Test]
    public function returnsSyncScopeIdWhenSet(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="syncScopeId">',
            '<value index="vDEF">dd4639dc-58a7-4648-a6ce-4950293a06db</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame('dd4639dc-58a7-4648-a6ce-4950293a06db', $subject->getSyncScopeId());
    }

    #[Test]
    public function returnsEmptyStringAsSyncScopeIdWhenNoConfigurationExists(): void
    {
        $flexForm = '';

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame('', $subject->getSyncScopeId());
    }

    #[Test]
    public function returnsEmptyStringAsSyncScopeIdWhenNoSyncScopeIdAreConfigured(): void
    {
        $flexForm = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>',
            '<T3FlexForms>',
            '<data>',
            '<sheet index="sDEF">',
            '<language index="lDEF">',
            '<field index="storagePid">',
            '<value index="vDEF">10</value>',
            '</field>',
            '</language>',
            '</sheet>',
            '</data>',
            '</T3FlexForms>',
        ]);

        $subject = new ImportConfiguration();

        $subject->_setProperty('configuration', $flexForm);

        self::assertSame('', $subject->getSyncScopeId());
    }
}
