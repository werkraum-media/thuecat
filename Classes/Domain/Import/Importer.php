<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;
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
        $translationLanguages = [];
        $defaultLanguage = 'de'; // fallback
        foreach ($this->siteFinder->getSiteByPageId($configuration->getStoragePid())->getLanguages() as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                $defaultLanguage = $siteLanguage->getLocale()->getLanguageCode();
            } else {
                $translationLanguages[$siteLanguage->getLocale()->getLanguageCode()] = $siteLanguage->getLanguageId();
            }
        }
        $context = new ResolverContext($configuration->getStoragePid(), $defaultLanguage, $configuration->getApiKey());
        $accumulatedPayload = new DataHandlerPayload();
        foreach ($urlProvider->getUrls() as $url) {
            $inputData = $this->fetchDataFromApi($url, $apiKey);
            $this->parser->parse($inputData, $defaultLanguage, $translationLanguages);
            $resolved = $this->resolver->resolve($this->parser->getDataHandlerPayload(), $context);
            $accumulatedPayload->mergeFrom($resolved);
        }

        // Snapshot before the loop drains the datamap. Translation rows added
        // by the resolver are excluded so the logger reports only the
        // default-language records the user expects to see counted.
        $loggerPayload = $accumulatedPayload->getDefaultLanguageDataMap();

        if ($accumulatedPayload->getDataMap() === [] && $accumulatedPayload->getCmdMap() === []) {
            return;
        }

        $iterations = 0;
        $maxIterations = 3;
        // DataHandler carries state across calls (substNEWwithIDs, datamap,
        // cmdmap, errors, …); reusing one instance across passes mixes state.
        // Each pass gets a fresh instance and the substNEWwithIDs maps get
        // merged so the logger sees every NEW→uid mapping the loop produced.
        $substNEWwithIDs = [];
        while ($accumulatedPayload->getDataMap() !== [] || $accumulatedPayload->getCmdMap() !== []) {
            if ($iterations >= $maxIterations) {
                throw new RuntimeException(
                    'Importer loop exceeded ' . $maxIterations . ' iterations; translations bucket: '
                    . json_encode($accumulatedPayload->getTranslations()),
                    1777148664
                );
            }
            $cmd = $this->fanOutCmdMap($accumulatedPayload->getCmdMap());
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($accumulatedPayload->getDataMap(), $cmd);
            $dataHandler->process_datamap();
            $dataHandler->process_cmdmap();
            /** @var array<string, int|string> $passSubst */
            $passSubst = $dataHandler->substNEWwithIDs;
            $substNEWwithIDs = $substNEWwithIDs + $passSubst;
            $accumulatedPayload->clearDataMap();
            $accumulatedPayload->clearCmdMap();
            $this->resolver->resolve($accumulatedPayload, $context);
            $iterations++;
        }

        $this->importLogger->writeLog(
            $configuration->getUid(),
            $loggerPayload,
            $substNEWwithIDs
        );
    }

    /**
     * Fan the staged cmdmap entries out into the nested shape DataHandler::start()
     * consumes: $cmd[$table][$uid][$command] = $value.
     *
     * @param array<string, array<int|string, list<array{0: string, 1: int|string}>>> $cmdMap
     *
     * @return array<string, array<int|string, array<string, int|string>>>
     */
    private function fanOutCmdMap(array $cmdMap): array
    {
        $result = [];
        foreach ($cmdMap as $table => $entriesByKey) {
            foreach ($entriesByKey as $key => $entries) {
                foreach ($entries as $entry) {
                    $result[$table][$key][$entry[0]] = $entry[1];
                }
            }
        }
        return $result;
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
