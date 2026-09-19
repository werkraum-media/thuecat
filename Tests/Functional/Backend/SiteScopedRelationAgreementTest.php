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

use ArrayAccess;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase;

/**
 * Asserts that a relation field's dropdown and its suggest wizard offer the
 * same records, over a fixture tree spanning two sites.
 *
 * The two surfaces reach the same condition by different routes and filter in
 * different queries, so agreement between them is the property under test
 * rather than the behaviour of either alone.
 */
class SiteScopedRelationAgreementTest extends AbstractImportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importPHPDataSet(__DIR__ . '/../Fixtures/Backend/TwoSitesWithTowns.php');
        $GLOBALS['TYPO3_REQUEST'] = $this->backendRequest();
    }

    #[Test]
    public function bothSurfacesOfferTheSameRecordsForTheFirstSite(): void
    {
        self::assertSame(
            [4001],
            $this->dropdownOffers(4900),
            'dropdown'
        );
        self::assertSame(
            $this->dropdownOffers(4900),
            $this->wizardOffers(4900, 4010),
            'the two surfaces must agree'
        );
    }

    #[Test]
    public function bothSurfacesOfferTheSameRecordsForTheSecondSite(): void
    {
        self::assertSame(
            [5001],
            $this->dropdownOffers(5900),
            'dropdown'
        );
        self::assertSame(
            $this->dropdownOffers(5900),
            $this->wizardOffers(5900, 5010),
            'the two surfaces must agree'
        );
    }

    #[Test]
    public function neitherSurfaceOffersTheOtherSitesTown(): void
    {
        $firstDropdown = $this->dropdownOffers(4900);
        $firstWizard = $this->wizardOffers(4900, 4010);

        // An empty offer would satisfy every assertNotContains below.
        self::assertContains(4001, $firstDropdown);
        self::assertContains(4001, $firstWizard);

        self::assertNotContains(5001, $firstDropdown);
        self::assertNotContains(5001, $firstWizard);
        self::assertNotContains(4001, $this->dropdownOffers(5900));
        self::assertNotContains(4001, $this->wizardOffers(5900, 5010));
    }

    #[Test]
    public function neitherSurfaceOffersATranslation(): void
    {
        $dropdown = $this->dropdownOffers(4900);
        $wizard = $this->wizardOffers(4900, 4010);

        self::assertContains(4001, $dropdown);
        self::assertContains(4001, $wizard);

        self::assertNotContains(4002, $dropdown);
        self::assertNotContains(4002, $wizard);
    }

    /**
     * The wizard renders record icons, which resolve their public path through
     * the request's normalizedParams.
     */
    private function backendRequest(): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
        ;
    }

    /**
     * The town uids a rendered attraction form offers.
     *
     * @return list<int>
     */
    private function dropdownOffers(int $attractionUid): array
    {
        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $GLOBALS['TYPO3_REQUEST'],
                'tableName' => 'tx_thuecat_tourist_attraction',
                'vanillaUid' => $attractionUid,
                'command' => 'edit',
            ],
            GeneralUtility::makeInstance(TcaDatabaseRecord::class)
        );

        $node = $result;
        foreach (['processedTca', 'columns', 'town', 'config', 'items'] as $key) {
            if (!is_array($node) || !isset($node[$key])) {
                return [];
            }
            $node = $node[$key];
        }

        $uids = [];
        foreach ((array)$node as $item) {
            // Items are SelectItem objects here, not arrays; both read through
            // ArrayAccess.
            $value = (is_array($item) || $item instanceof ArrayAccess) ? ($item['value'] ?? null) : null;
            if (is_numeric($value) && (int)$value > 0) {
                $uids[] = (int)$value;
            }
        }
        sort($uids);

        return $uids;
    }

    /**
     * The town uids the suggest wizard returns for the same field, searching a
     * title every town in the fixture shares.
     *
     * @return list<int>
     */
    private function wizardOffers(int $attractionUid, int $pid): array
    {
        $request = $this->backendRequest()
            ->withParsedBody([
                'value' => 'Shared',
                'tableName' => 'tx_thuecat_tourist_attraction',
                'fieldName' => 'town',
                'uid' => $attractionUid,
                'pid' => $pid,
            ])
        ;

        $response = GeneralUtility::makeInstance(SuggestWizardController::class)->searchAction($request);
        $rows = json_decode((string)$response->getBody(), true);

        $uids = [];
        foreach ((array)$rows as $row) {
            $uid = is_array($row) ? ($row['uid'] ?? null) : null;
            if (is_numeric($uid)) {
                $uids[] = (int)$uid;
            }
        }
        sort($uids);

        return $uids;
    }
}
