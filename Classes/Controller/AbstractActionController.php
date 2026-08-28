<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use WerkraumMedia\ThueCat\Domain\Model\Frontend\Base;
use WerkraumMedia\ThueCat\Frontend\Cache\TeaserRenderer;
use WerkraumMedia\ThueCat\Frontend\MetaInformation\MetaInformationService;

class AbstractActionController extends ActionController
{
    protected TeaserRenderer $teaserRenderer;
    protected MetaInformationService $metaInformationService;
    public function injectTeaserRenderer(TeaserRenderer $teaserRenderer): void
    {
        $this->teaserRenderer = $teaserRenderer;
    }

    public function injectMetaInformationService(MetaInformationService $metaInformationService): void
    {
        $this->metaInformationService = $metaInformationService;
    }

    /**
     * Renders each record's item template, serving stored HTML where it exists.
     *
     * @param iterable<mixed> $records
     *
     * @return list<string>
     */
    protected function renderItems(iterable $records, string $detailPageUidSettingName): array
    {
        $detailPageUid = $this->pageUidFromSettings($detailPageUidSettingName);
        $languageId = $this->languageId();
        $viewPaths = $this->resolveViewPaths();
        /** @var array<string, mixed> $settings */
        $settings = $this->settings;

        $items = [];
        foreach ($records as $record) {
            if (!$record instanceof Base) {
                continue;
            }
            $items[] = $this->teaserRenderer->render(
                $record,
                $detailPageUid,
                $languageId,
                $settings,
                $viewPaths,
                $this->request
            );
        }

        return $items;
    }

    protected function languageId(): int
    {
        return $this->request->getAttribute('language')?->getLanguageId() ?? 0;
    }

    /** A page uid from `settings.page.pid.*`, 0 when unconfigured. */
    protected function pageUidFromSettings(string $name): int
    {
        $pageSettings = $this->settings['page'] ?? [];
        $pidSettings = is_array($pageSettings) ? ($pageSettings['pid'] ?? []) : [];
        $pid = is_array($pidSettings) ? ($pidSettings[$name] ?? null) : null;

        return is_scalar($pid) ? (int)$pid : 0;
    }

    /**
     * The plugin's own template paths, so overrides apply to separately
     * rendered items too.
     *
     * @return array{templateRootPaths?: array<int, string>, partialRootPaths?: array<int, string>, layoutRootPaths?: array<int, string>}
     */
    protected function resolveViewPaths(): array
    {
        $framework = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        /** @var array{templateRootPaths?: array<int, string>, partialRootPaths?: array<int, string>, layoutRootPaths?: array<int, string>} $paths */
        $paths = is_array($framework['view'] ?? null) ? $framework['view'] : [];

        return $paths;
    }
}
