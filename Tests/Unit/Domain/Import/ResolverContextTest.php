<?php

declare(strict_types=1);

/*
 * Copyright (C) 2024 werkraum-media
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

namespace WerkraumMedia\ThueCat\Tests\Unit\Domain\Import;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WerkraumMedia\ThueCat\Domain\Import\ResolverContext;

/**
 * Pinpoint coverage for ResolverContext::promoteNewKeys — the bridge
 * between Importer rounds. Each test guards a single property of the
 * promotion contract so a regression points at the exact violated rule.
 */
final class ResolverContextTest extends TestCase
{
    private const REMOTE_A = 'https://thuecat.org/resources/aaaa';
    private const REMOTE_B = 'https://thuecat.org/resources/bbbb';

    #[Test]
    public function promoteRewritesNewPlaceholderToUidString(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = 'NEW_abc';

        $context->promoteNewKeys(['NEW_abc' => 42]);

        self::assertSame(
            '42',
            $context->remoteIdToKey[self::REMOTE_A],
            'promoteNewKeys must rewrite NEW… placeholder to the assigned uid as a string. '
            . 'If this fails, the persistent map still carries the NEW… placeholder into '
            . 'round 2 and downstream wiring will use the wrong key.'
        );
    }

    #[Test]
    public function promoteCastsIntegerSubstValueToString(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = 'NEW_abc';

        $context->promoteNewKeys(['NEW_abc' => 7]);

        self::assertSame(
            '7',
            $context->remoteIdToKey[self::REMOTE_A],
            'DataHandler may hand integer uids in substNEWwithIDs. The map type is '
            . 'array<string, string>; a leaked int would break === comparisons '
            . 'downstream (e.g. ctype_digit() on int throws TypeError).'
        );
    }

    #[Test]
    public function promoteLeavesAlreadyPromotedUidsUntouched(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = '7';

        $context->promoteNewKeys([]);

        self::assertSame(
            '7',
            $context->remoteIdToKey[self::REMOTE_A],
            'A remote_id already promoted in a prior round must survive promotion '
            . 'with an empty subst array unchanged.'
        );
    }

    #[Test]
    public function promoteIgnoresSubstEntriesWithNoMatchingKey(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = 'NEW_known';

        $context->promoteNewKeys([
            'NEW_known' => 1,
            'NEW_unrelated' => 2,
        ]);

        self::assertSame(
            [self::REMOTE_A => '1'],
            $context->remoteIdToKey,
            'promoteNewKeys must (a) rewrite the matched entry and (b) not invent new '
            . 'remote_id entries from unrelated subst keys. The map is keyed by '
            . 'remote_id; subst keys are NEW… placeholders, never remote_ids.'
        );
    }

    #[Test]
    public function promotePreservesRemoteIdsNotMentionedBySubst(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = 'NEW_a';
        $context->remoteIdToKey[self::REMOTE_B] = 'NEW_b';

        $context->promoteNewKeys(['NEW_a' => 11]);

        self::assertSame(
            '11',
            $context->remoteIdToKey[self::REMOTE_A],
            'REMOTE_A had a matching subst entry and must be promoted.'
        );
        self::assertSame(
            'NEW_b',
            $context->remoteIdToKey[self::REMOTE_B],
            'REMOTE_B had no matching subst entry and must keep its NEW… placeholder. '
            . 'A regression here would silently drop or mutate unrelated rows in the map.'
        );
    }

    #[Test]
    public function promoteAcceptsNumericStringSubstValue(): void
    {
        $context = new ResolverContext(storagePid: 10);
        $context->remoteIdToKey[self::REMOTE_A] = 'NEW_abc';

        $context->promoteNewKeys(['NEW_abc' => '42']);

        self::assertSame(
            '42',
            $context->remoteIdToKey[self::REMOTE_A],
            'substNEWwithIDs is typed array<string, int|string>; numeric string values '
            . 'must round-trip through promoteNewKeys unchanged.'
        );
    }

    #[Test]
    public function promoteWithEmptyMapIsNoop(): void
    {
        $context = new ResolverContext(storagePid: 10);

        $context->promoteNewKeys(['NEW_abc' => 42]);

        self::assertSame(
            [],
            $context->remoteIdToKey,
            'Empty remoteIdToKey must stay empty regardless of subst contents.'
        );
    }
}