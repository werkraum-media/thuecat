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

namespace WerkraumMedia\ThueCat\Tests\Functional\TouristAttraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Dto\TouristAttractionDemand;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\MeetingVenue;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\TouristAttraction;
use WerkraumMedia\ThueCat\Domain\Repository\Frontend\TouristAttractionRepository;
use WerkraumMedia\ThueCat\Tests\Functional\AbstractFunctionalTestCase;

final class TouristAttractionRepositoryTest extends AbstractFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    #[Test]
    public function hydratesEachRecordAsTheClassOfItsType(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TouristAttraction/AttractionAndMeetingVenue.csv');

        $classes = [];
        foreach ($this->get(TouristAttractionRepository::class)->findByDemand(new TouristAttractionDemand()) as $record) {
            $this->assertInstanceOf(TouristAttraction::class, $record);
            $classes[(int)$record->getUid()] = $record::class;
        }
        ksort($classes);

        $this->assertSame([1 => TouristAttraction::class, 2 => MeetingVenue::class], $classes);
    }
}
