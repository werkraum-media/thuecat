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

namespace WerkraumMedia\ThueCat\Service;

use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Tells whether a message of a chain still waits in the doctrine transport, so a failure listener knows whether the
 * chain goes on without it. A message being handled is still stored, but already marked as delivered.
 */
final readonly class WaitingMessages
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @param callable(object): bool $matches
     */
    public function exists(callable $matches): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_messenger_messages');
        $bodies = $queryBuilder
            ->select('body')
            ->from('sys_messenger_messages')
            ->where($queryBuilder->expr()->isNull('delivered_at'))
            ->executeQuery()
            ->fetchFirstColumn()
        ;

        $serializer = new PhpSerializer();
        foreach ($bodies as $body) {
            try {
                $waiting = $serializer->decode(['body' => is_string($body) ? $body : ''])->getMessage();
            } catch (MessageDecodingFailedException) {
                continue;
            }

            if ($matches($waiting)) {
                return true;
            }
        }

        return false;
    }
}
