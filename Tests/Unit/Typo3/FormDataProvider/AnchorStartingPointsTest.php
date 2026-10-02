<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Tests\Unit\Typo3\FormDataProvider;

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use WerkraumMedia\ThueCat\Import\Settings\AnchorKind;
use WerkraumMedia\ThueCat\Import\Settings\AnchorPair;
use WerkraumMedia\ThueCat\Import\Settings\AnchorScope;
use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorResolver;
use WerkraumMedia\ThueCat\Typo3\FormDataProvider\AnchorStartingPoints;

class AnchorStartingPointsTest extends TestCase
{
    #[Test]
    public function replacesTheMarkerWithTheResolvedParent(): void
    {
        $resolver = $this->createMock(CategoryAnchorResolver::class);
        $resolver->expects(self::once())
            ->method('resolvePair')
            ->with(self::isInstanceOf(Site::class), 'tx_thuecat_trail', AnchorKind::Keyword)
            ->willReturn(new AnchorPair(140, 360, new AnchorScope('trails')))
        ;

        $result = (new AnchorStartingPoints($resolver))->addData(
            $this->formData('###THUECAT_ANCHOR:tx_thuecat_trail:keywords###')
        );

        self::assertSame('140', $this->startingPointsOf($result));
    }

    #[Test]
    public function resolvesTheCategoryKind(): void
    {
        $resolver = $this->createMock(CategoryAnchorResolver::class);
        $resolver->expects(self::once())
            ->method('resolvePair')
            ->with(self::isInstanceOf(Site::class), 'tx_thuecat_tourist_attraction', AnchorKind::Category)
            ->willReturn(new AnchorPair(100, 320, new AnchorScope('thuecat')))
        ;

        $result = (new AnchorStartingPoints($resolver))->addData(
            $this->formData('###THUECAT_ANCHOR:tx_thuecat_tourist_attraction:category###')
        );

        self::assertSame('100', $this->startingPointsOf($result));
    }

    // Core's own treatment of an unset ###SITE### value: the whole tree.
    #[Test]
    public function anUnresolvedAnchorBecomesZero(): void
    {
        $resolver = self::createStub(CategoryAnchorResolver::class);
        $resolver->method('resolvePair')->willReturn(new AnchorPair());

        $result = (new AnchorStartingPoints($resolver))->addData(
            $this->formData('###THUECAT_ANCHOR:tx_thuecat_trail:keywords###')
        );

        self::assertSame('0', $this->startingPointsOf($result));
    }

    #[Test]
    public function withoutASiteTheMarkerBecomesZero(): void
    {
        $resolver = $this->createMock(CategoryAnchorResolver::class);
        $resolver->expects(self::never())->method('resolvePair');

        $result = (new AnchorStartingPoints($resolver))->addData(
            $this->formData('###THUECAT_ANCHOR:tx_thuecat_trail:keywords###', new NullSite())
        );

        self::assertSame('0', $this->startingPointsOf($result));
    }

    #[Test]
    public function leavesCoresSiteMarkerAlone(): void
    {
        $resolver = $this->createMock(CategoryAnchorResolver::class);
        $resolver->expects(self::never())->method('resolvePair');

        $result = (new AnchorStartingPoints($resolver))->addData(
            $this->formData('###SITE:settings.import.thuecat.keywords.parent###')
        );

        self::assertSame('###SITE:settings.import.thuecat.keywords.parent###', $this->startingPointsOf($result));
    }

    #[Test]
    public function leavesFieldsWithoutStartingPointsAlone(): void
    {
        $resolver = self::createStub(CategoryAnchorResolver::class);
        $input = [
            'site' => new Site('test', 1, []),
            'processedTca' => ['columns' => ['title' => ['config' => ['type' => 'input']]]],
        ];

        self::assertSame($input, (new AnchorStartingPoints($resolver))->addData($input));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(string $startingPoints, Site|NullSite|null $site = null): array
    {
        return [
            'site' => $site ?? new Site('test', 1, []),
            'processedTca' => [
                'columns' => [
                    'keywords' => [
                        'config' => [
                            'type' => 'category',
                            'treeConfig' => ['startingPoints' => $startingPoints],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $result
     */
    private function startingPointsOf(array $result): mixed
    {
        $value = $result;
        foreach (['processedTca', 'columns', 'keywords', 'config', 'treeConfig', 'startingPoints'] as $key) {
            self::assertIsArray($value);
            $value = $value[$key] ?? null;
        }

        return $value;
    }
}
