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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import\Parser\Entity;

use PHPUnit\Framework\Attributes\Test;
use WerkraumMedia\ThueCat\Import\Parser\DataHandlerPayload;
use WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Import\Parser\Entity\TouristAttractionEntity;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

/** Keywords travel in their own bucket, never among the categories. */
class KeywordBucketTest extends AbstractImportTestCase
{
    private const REMOTE_ID = 'https://thuecat.org/resources/165868194223-zmqf';

    #[Test]
    public function keywordsNeverAppearAmongCategories(): void
    {
        $payload = new DataHandlerPayload();
        $payload->addEntity($this->parseFixture());

        $categories = $payload->getCategories()[TouristAttractionEntity::TABLE][self::REMOTE_ID] ?? [];

        self::assertNotSame([], $categories, 'The fixture must produce type categories.');
        self::assertSame([], array_filter(
            $categories,
            static fn (array $entry): bool => str_starts_with($entry['remoteId'], 'keyword:')
        ));
    }

    #[Test]
    public function keywordBucketIsAbsentWhenTheNodeCarriesNone(): void
    {
        $entity = $this->parseFixture();

        self::assertArrayNotHasKey(AbstractEntity::KEYWORD_BUCKET, $entity->getTransients());
    }

    private function parseFixture(): TouristAttractionEntity
    {
        $node = $this->nodeFromFixture('165868194223-zmqf.json', 'schema:TouristAttraction');
        self::assertNotNull($node);

        $entity = new TouristAttractionEntity();
        $entity->parse($node, 'de', new ParserContext(0));

        return $entity;
    }
}
