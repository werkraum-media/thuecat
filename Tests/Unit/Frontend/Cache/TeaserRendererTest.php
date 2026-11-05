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

namespace WerkraumMedia\ThueCat\Tests\Unit\Frontend\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MeetingVenue;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Trail;
use WerkraumMedia\ThueCat\Frontend\Cache\CacheIdentifierFactory;
use WerkraumMedia\ThueCat\Frontend\Cache\TeaserRenderer;

class TeaserRendererTest extends TestCase
{
    public static function records(): iterable
    {
        yield 'attraction' => [new TouristAttraction(), 'TouristAttraction/ListItem', 'attraction'];
        yield 'record type subclass renders its parent template' => [new MeetingVenue(), 'TouristAttraction/ListItem', 'attraction'];
        yield 'other table' => [new Trail(), 'Trail/ListItem', 'trail'];
    }

    #[Test]
    #[DataProvider('records')]
    public function rendersTheListItemTemplateOfTheTableRootModel(Base $record, string $expectedTemplate, string $expectedVariable): void
    {
        $cache = self::createStub(FrontendInterface::class);
        $cache->method('get')->willReturn(false);
        $cacheManager = self::createStub(CacheManager::class);
        $cacheManager->method('getCache')->willReturn($cache);

        $assigned = [];
        $rendered = '';
        $view = self::createStub(ViewInterface::class);
        $view->method('assignMultiple')->willReturnCallback(static function (array $values) use (&$assigned, $view): ViewInterface {
            $assigned = $values;
            return $view;
        });
        $view->method('render')->willReturnCallback(static function (string $template = '') use (&$rendered): string {
            $rendered = $template;
            return '';
        });
        $viewFactory = self::createStub(ViewFactoryInterface::class);
        $viewFactory->method('create')->willReturn($view);

        $tables = [
            TouristAttraction::class => 'tx_thuecat_tourist_attraction',
            MeetingVenue::class => 'tx_thuecat_tourist_attraction',
            Trail::class => 'tx_thuecat_trail',
        ];
        $dataMapper = self::createStub(DataMapper::class);
        $dataMapper->method('getDataMap')->willReturnCallback(
            static fn (string $className): DataMap => new DataMap($className, $tables[$className] ?? 'test:unmapped')
        );

        $subject = new TeaserRenderer($cacheManager, $viewFactory, new CacheIdentifierFactory(), $dataMapper);
        $subject->render($record, 0, 0, [], [], self::createStub(ServerRequestInterface::class));

        self::assertSame($expectedTemplate, $rendered);
        self::assertArrayHasKey($expectedVariable, $assigned);
        self::assertSame($record, $assigned[$expectedVariable]);
    }
}
