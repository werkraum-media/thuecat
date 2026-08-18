<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Functional\Backend;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

// An editor opening a place record must see the keyword tree in the keyword
// field and the type-category tree in the categories field — never each other's.
// The anchors come from site settings, so the assertion is that core resolved
// ###SITE:### into the configured uids.
class KeywordFieldScopingTest extends AbstractImportTestCase
{
    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
    ];

    #[Test]
    public function keywordFieldOffersTheKeywordAnchorOnly(): void
    {
        self::assertSame('500', $this->startingPointsFor('keywords'));
    }

    #[Test]
    public function categoryFieldOffersTheCategoryAnchorOnly(): void
    {
        self::assertSame('300', $this->startingPointsFor('categories'));
    }

    /**
     * The prepared TCA for one field of a place record on the fixture's storage
     * page, with the site markers already resolved.
     */
    private function startingPointsFor(string $field): string
    {
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Frontend/TouristAttractionsForFilter.php');

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute(
            'applicationType',
            SystemEnvironmentBuilder::REQUESTTYPE_BE
        );

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $GLOBALS['TYPO3_REQUEST'],
                'tableName' => 'tx_thuecat_tourist_attraction',
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            GeneralUtility::makeInstance(TcaDatabaseRecord::class)
        );

        // Walked step by step: the compiler returns a plain array, so each hop
        // is mixed until it is proven to be one.
        $node = $result;
        foreach (['processedTca', 'columns', $field, 'config', 'treeConfig', 'startingPoints'] as $key) {
            if (!is_array($node) || !isset($node[$key])) {
                return '';
            }
            $node = $node[$key];
        }

        return is_scalar($node) ? (string)$node : '';
    }
}
