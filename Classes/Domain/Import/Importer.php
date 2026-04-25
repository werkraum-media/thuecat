<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\InvalidUrlProviderException;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;

class Importer
{
    public function __construct(
        private readonly Parser $parser,
        private readonly FetchData $fetchData,
        private readonly SiteFinder $siteFinder,
        private readonly Resolver $resolver,
        private readonly ImportLogger $importLogger,
        #[AutowireLocator(services: 'import.url.provider')]
        private readonly ServiceLocator $urlProviders
    ) {
    }

    public function importConfiguration(ImportConfiguration $configuration): void
    {
        $urlProvider = $this->getProviderForConfiguration($configuration);
        if (!$urlProvider instanceof UrlProvider) {
            throw new InvalidUrlProviderException('No URL Provider available for given configuration.', 1629296635);
        }

        $apiKey = $configuration->getApiKey();
        $language = $this->resolveDefaultLanguage($configuration->getStoragePid());
        $accumulatedPayload = [];
        foreach ($urlProvider->getUrls() as $url) {
            $inputData = $this->fetchDataFromApi($url, $apiKey);
            $this->parser->parse($inputData, $language);
            $dataHandlerPayload = $this->resolver->resolve($this->parser->getDataHandlerPayload(), new ResolverContext($configuration->getStoragePid(), $language, $configuration->getApiKey()));
            $accumulatedPayload = $this->mergePayload($accumulatedPayload, $dataHandlerPayload->getPayload());
        }

        if ($accumulatedPayload === []) {
            return;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($accumulatedPayload, []);
        $dataHandler->process_datamap();

        /** @var array<string, int|string> $substNEWwithIDs */
        $substNEWwithIDs = $dataHandler->substNEWwithIDs;
        $this->importLogger->writeLog(
            $configuration->getUid(),
            $accumulatedPayload,
            $substNEWwithIDs
        );
    }

    /**
     * Per-table merge so multiple URLs contributing to the same table
     * stay in one entry. Same-key collisions across URLs let the later
     * one win — the resolver assigns stable existing-uid keys for known
     * remote_ids, so "collision" means the same record was emitted twice
     * and the latest payload is the one to keep.
     *
     * @param array<string, array<int|string, array<string, mixed>>> $base
     * @param array<string, array<int|string, array<string, mixed>>> $addition
     *
     * @return array<string, array<int|string, array<string, mixed>>>
     */
    private function mergePayload(array $base, array $addition): array
    {
        foreach ($addition as $table => $rows) {
            $base[$table] = array_merge($base[$table] ?? [], $rows);
        }
        return $base;
    }

    /**
     * Two-letter language code (e.g. "de") of the target site's default language,
     * used by the Parser to pick the matching @language entry from the JSON-LD.
     * Falls back to "de" when the storagePid is not attached to any site.
     */
    private function resolveDefaultLanguage(int $storagePid): string
    {
        if ($storagePid <= 0) {
            return 'de';
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($storagePid);
        } catch (SiteNotFoundException) {
            return 'de';
        }

        return $site->getDefaultLanguage()->getLocale()->getLanguageCode();
    }

    private function getProviderForConfiguration(ImportConfiguration $configuration): ?UrlProvider
    {
        foreach ($this->urlProviders as $provider) {
            if ($provider->canProvideForConfiguration($configuration)) {
                return $provider->createWithConfiguration($configuration);
            }
        }

        return null;
    }

    private function fetchDataFromApi(string $url, string $apiKey): array
    {
        $response = $this->fetchData->jsonLDFromUrl($url, $apiKey === '' ? null : $apiKey);
        $graph = $response['@graph'] ?? [];
        return is_array($graph) ? $graph : [];
    }
}
