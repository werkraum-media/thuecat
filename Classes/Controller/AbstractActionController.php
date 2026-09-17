<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Controller;

use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Repository;
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

    /** The uid an editor pinned to the plugin, 0 when none. */
    protected function selectedRecordUid(): int
    {
        $selected = $this->settings['selectedRecord'] ?? null;

        return is_scalar($selected) ? (int)$selected : 0;
    }

    /**
     * The pinned record, or null when the editor pinned none.
     *
     * The pick is a default-language uid, the same in every language: Extbase
     * overlays it for the request's language.
     *
     * A pinned uid that no longer resolves also yields null; the caller must not
     * fall back to its own argument, or an editor's pick could be replaced by a
     * URL.
     */
    protected function selectedRecord(Repository $repository): ?Base
    {
        $uid = $this->selectedRecordUid();
        if ($uid === 0) {
            return null;
        }

        $record = $repository->findByUid($uid);

        return $record instanceof Base ? $record : null;
    }

    /**
     * Tags a detail view that resolved no record.
     *
     * Core tags the rows a query returns, so an empty result tags nothing and
     * the entry could never be flushed. A configured uid tags that uid, which
     * outlives the record and so brings the page back when it returns;
     * otherwise the table is all there is to key on.
     */
    protected function addCacheTagForEmptyDetailView(string $table): void
    {
        $uid = $this->selectedRecordUid();
        $tag = $uid > 0 ? $table . '_' . $uid : $table;

        $this->request->getAttribute('frontend.cache.collector')?->addCacheTags(new CacheTag($tag));
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
