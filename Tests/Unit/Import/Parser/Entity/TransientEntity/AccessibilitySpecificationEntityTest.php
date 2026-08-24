<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity\TransientEntity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TransientEntity\AccessibilitySpecificationEntity;

/**
 * Pins the untagged-typed-enum reads: every value asserted here carries no
 * language tag, which is the fallback path text extraction must keep.
 */
class AccessibilitySpecificationEntityTest extends TestCase
{
    #[Test]
    public function readsUntaggedCertificationStatus(): void
    {
        $subject = new AccessibilitySpecificationEntity();
        $subject->configure([
            'thuecat:accessibilityCertification' => [
                'thuecat:accessibilityCertificationStatus' => [
                    '@type' => 'thuecat:AccessibilityCertificationStatus',
                    '@value' => 'thuecat:CertificationValid',
                ],
            ],
        ], 'de');

        self::assertSame(
            ['accessibilityCertificationStatus' => 'CertificationValid'],
            $subject->toArray()
        );
    }

    #[Test]
    public function readsUntaggedCertificationLevelFromLegacyFlatShape(): void
    {
        $subject = new AccessibilitySpecificationEntity();
        $subject->configure([
            'thuecat:accessibilityCertification' => [
                'thuecat:certificationAccessibilityWalking' => [
                    '@type' => 'thuecat:CertificationLevel',
                    '@value' => 'thuecat:PartiallyAccessible',
                ],
            ],
        ], 'de');

        self::assertSame(
            ['certificationAccessibilityWalking' => 'PartiallyAccessible'],
            $subject->toArray()
        );
    }

    #[Test]
    public function readsUntaggedCertificationLevelFromPropertyValueWrapper(): void
    {
        $subject = new AccessibilitySpecificationEntity();
        $subject->configure([
            'thuecat:accessibilityCertification' => [
                'thuecat:certificationAccessibilityVisual' => [
                    '@type' => 'schema:PropertyValue',
                    'schema:value' => [
                        '@type' => 'thuecat:CertificationLevel',
                        '@value' => 'thuecat:FullyAccessible',
                    ],
                ],
            ],
        ], 'de');

        self::assertSame(
            ['certificationAccessibilityVisual' => 'FullyAccessible'],
            $subject->toArray()
        );
    }

    #[Test]
    public function readsUntaggedSearchCriteriaMembers(): void
    {
        $subject = new AccessibilitySpecificationEntity();
        $subject->configure([
            'thuecat:accessibilitySearchCriteria' => [
                [
                    '@type' => 'thuecat:facilityAccessibilityWalking',
                    '@value' => 'thuecat:Handrail',
                ],
                [
                    '@type' => 'thuecat:facilityAccessibilityVisual',
                    '@value' => 'thuecat:BrailleSignage',
                ],
            ],
        ], 'de');

        self::assertSame(
            [
                'accessibilitySearchCriteria' => [
                    'facilityAccessibilityWalking' => ['Handrail'],
                    'facilityAccessibilityVisual' => ['BrailleSignage'],
                ],
            ],
            $subject->toArray()
        );
    }

    /** The localised path stays language-selective; only untagged falls back. */
    #[Test]
    public function readsShortDescriptionForRequestedLanguage(): void
    {
        $node = [
            'thuecat:shortDescriptionAccessibilityWalking' => [
                ['@language' => 'de', '@value' => 'Stufenloser Zugang'],
                ['@language' => 'en', '@value' => 'Step-free access'],
            ],
        ];

        $german = new AccessibilitySpecificationEntity();
        $german->configure($node, 'de');
        self::assertSame(
            ['shortDescriptionAccessibilityWalking' => 'Stufenloser Zugang'],
            $german->toArray()
        );

        $english = new AccessibilitySpecificationEntity();
        $english->configure($node, 'en');
        self::assertSame(
            ['shortDescriptionAccessibilityWalking' => 'Step-free access'],
            $english->toArray()
        );
    }
}
