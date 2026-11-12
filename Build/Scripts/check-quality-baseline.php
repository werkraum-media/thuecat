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

// MSI only with the summary of a full run; the gate's MSI covers changed lines alone.
// Usage: php Build/Scripts/check-quality-baseline.php [var/infection/summary.json]

$root = dirname(__DIR__, 2);

$baseline = json_decode((string)file_get_contents($root . '/Build/quality-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($baseline)) {
    fwrite(STDERR, 'Build/quality-baseline.json holds no object.' . PHP_EOL);
    exit(1);
}

$coverage = new DOMDocument();
if (!is_file($root . '/.coverage/coverage-xml/index.xml') || !$coverage->load($root . '/.coverage/coverage-xml/index.xml')) {
    fwrite(STDERR, 'No coverage report in .coverage/coverage-xml.' . PHP_EOL);
    exit(1);
}

$xpath = new DOMXPath($coverage);
$xpath->registerNamespace('c', 'https://schema.phpunit.de/coverage/1.0');
$lines = '/c:phpunit/c:project/c:directory/c:totals/c:lines/';
$lineCoverage = $xpath->evaluate('string(' . $lines . '@percent)');
$executable = $xpath->evaluate('string(' . $lines . '@executable)');
$executed = $xpath->evaluate('string(' . $lines . '@executed)');
if (!is_numeric($lineCoverage) || !is_numeric($executable) || !is_numeric($executed)) {
    fwrite(STDERR, 'The coverage report carries no line total.' . PHP_EOL);
    exit(1);
}

$actual = [
    'lineCoverage' => (float)$lineCoverage,
    'uncoveredLines' => (int)$executable - (int)$executed,
];

$infectionLog = $argv[1] ?? null;
if (is_string($infectionLog)) {
    $log = json_decode((string)file_get_contents($infectionLog), true, 512, JSON_THROW_ON_ERROR);
    $msi = is_array($log) && is_array($log['stats'] ?? null) ? ($log['stats']['msi'] ?? null) : null;
    if (!is_int($msi) && !is_float($msi)) {
        fwrite(STDERR, $infectionLog . ' carries no stats.msi.' . PHP_EOL);
        exit(1);
    }

    $actual['msi'] = (float)$msi;
}

$failed = false;
foreach ($actual as $name => $value) {
    $expected = $baseline[$name] ?? null;
    if (!is_int($expected) && !is_float($expected)) {
        fwrite(STDERR, 'Build/quality-baseline.json carries no ' . $name . '.' . PHP_EOL);
        exit(1);
    }

    if (is_int($value)) {
        printf('%-14s %6d   baseline %6d' . PHP_EOL, $name, $value, $expected);
        $worse = $value > $expected;
        $better = $value < $expected;
    } else {
        printf('%-14s %6.2f%%  baseline %6.2f%%' . PHP_EOL, $name, $value, $expected);
        $worse = $value < $expected;
        $better = $value > $expected;
    }

    if ($worse) {
        $failed = true;
        echo '  worse than the baseline' . PHP_EOL;
    } elseif ($better) {
        echo '  better than the baseline: update it in Build/quality-baseline.json' . PHP_EOL;
    }
}

exit($failed ? 1 : 0);
