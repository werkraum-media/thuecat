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

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Support;

/** Derives a stable identity for editor-typed keywords, which carry none upstream. */
final class FreeTextKeyword
{
    public const REMOTE_ID_PREFIX = 'keyword:text:';

    // mb_* throughout: strtolower() is byte-wise, so 'Ölmühle' would survive
    // unchanged and split one keyword across two rows.
    public static function identity(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    public static function title(string $value): string
    {
        return mb_convert_case(self::identity($value), MB_CASE_TITLE, 'UTF-8');
    }

    public static function remoteId(string $value): string
    {
        return self::REMOTE_ID_PREFIX . self::identity($value);
    }
}
