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

namespace WerkraumMedia\ThueCat\Tests\Unit\Import;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Import\CollectedKeyword;
use WerkraumMedia\ThueCat\Import\Parser\ParserContext;
use WerkraumMedia\ThueCat\Import\ResolverContext;

/**
 * Keywords accumulate run-scoped so the flush after the last root sees the
 * complete set per owner — the precondition for deciding what to reap.
 */
final class KeywordCollectorTest extends TestCase
{
    private const OWNER = 'tx_thuecat_tourist_attraction';

    private const TERM = 'keyword:https://thuecat.org/resources/475728955106-qdcc';

    #[Test]
    public function startsEmpty(): void
    {
        self::assertSame([], $this->context()->collectedKeywords);
    }

    #[Test]
    public function keepsCollectionOrder(): void
    {
        $context = $this->context();

        $context->collectKeyword($this->keyword('42', self::TERM, 'Landeshauptstadt Erfurt'));
        $context->collectKeyword($this->keyword('42', 'keyword:text:theater', 'Theater'));

        self::assertSame(
            [self::TERM, 'keyword:text:theater'],
            array_map(static fn (CollectedKeyword $k): string => $k->remoteId, $context->collectedKeywords)
        );
    }

    // Shared terms get resolved once per referencing root.
    #[Test]
    public function ignoresARepeatedClaimByTheSameOwner(): void
    {
        $context = $this->context();

        $context->collectKeyword($this->keyword('42', self::TERM, 'Landeshauptstadt Erfurt'));
        $context->collectKeyword($this->keyword('42', self::TERM, 'Landeshauptstadt Erfurt'));

        self::assertCount(1, $context->collectedKeywords);
    }

    #[Test]
    public function keepsTheSameKeywordForDifferentOwners(): void
    {
        $context = $this->context();

        $context->collectKeyword($this->keyword('42', self::TERM, 'Landeshauptstadt Erfurt'));
        $context->collectKeyword($this->keyword('43', self::TERM, 'Landeshauptstadt Erfurt'));

        self::assertCount(2, $context->collectedKeywords);
    }

    #[Test]
    public function keepsTheSameKeywordForDifferentFields(): void
    {
        $context = $this->context();

        $context->collectKeyword($this->keyword('42', self::TERM, 'Erfurt', 'keywords'));
        $context->collectKeyword($this->keyword('42', self::TERM, 'Erfurt', 'other_keywords'));

        self::assertCount(2, $context->collectedKeywords);
    }

    #[Test]
    public function carriesTheParentSoTheTreeCanBeBuiltAtFlush(): void
    {
        $context = $this->context();

        $context->collectKeyword(new CollectedKeyword(
            self::OWNER,
            '42',
            'keywords',
            self::TERM,
            'Landeshauptstadt Erfurt',
            'keyword:https://thuecat.org/resources/155933862969-mofh',
        ));

        self::assertSame(
            'keyword:https://thuecat.org/resources/155933862969-mofh',
            $context->collectedKeywords[0]->parentRemoteId
        );
    }

    #[Test]
    public function parentIsOptionalForTopLevelKeywords(): void
    {
        $context = $this->context();

        $context->collectKeyword($this->keyword('42', 'keyword:text:theater', 'Theater'));

        self::assertCount(1, $context->collectedKeywords);
        self::assertNull($context->collectedKeywords[0]->parentRemoteId);
    }

    private function context(): ResolverContext
    {
        return new ResolverContext(10, new ParserContext(0));
    }

    private function keyword(
        string $ownerKey,
        string $remoteId,
        string $title,
        string $field = 'keywords'
    ): CollectedKeyword {
        return new CollectedKeyword(self::OWNER, $ownerKey, $field, $remoteId, $title);
    }
}
