<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Extension;

return (static function (): array {
    $iconFiles = GeneralUtility::getFilesInDir(
        GeneralUtility::getFileAbsFileName(
            Extension::getIconPath()
        )
    );

    if (is_array($iconFiles) === false) {
        return [];
    }

    $icons = [];
    foreach ($iconFiles as $iconFile) {
        $identifier = str_replace('.svg', '', $iconFile);
        $icons[$identifier] = [
            'provider' => SvgIconProvider::class,
            'source' => Extension::getIconPath() . $iconFile,
        ];
    }

    return $icons;
})();
