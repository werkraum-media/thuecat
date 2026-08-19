<?php

declare(strict_types=1);

// KeywordImportPreState with the keyword storage folder (30) hidden. The
// anchor and its tree still live there; a re-import must reuse them rather
// than build a second tree beside them, so keyword identity must not depend
// on the page being visible in the frontend.
/** @var array<string, list<array<string, string>>> $preState */
$preState = require __DIR__ . '/KeywordImportPreState.php';

foreach ($preState['pages'] as $index => $page) {
    if ($page['uid'] === '30') {
        $preState['pages'][$index]['hidden'] = '1';
    }
}

return $preState;
