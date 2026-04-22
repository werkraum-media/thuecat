<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Parser;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\InvalidUrlProviderException;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;

class Importer
{
    public function __construct(
        private readonly Parser         $parser,
        private readonly FetchData      $fetchData,
        #[AutowireLocator(services: 'import.url.provider')]
        private readonly ServiceLocator $urlProviders)
    {
    }

    public function importConfiguration(ImportConfiguration $configuration): void
    {
        $urlProvider = $this->getProviderForConfiguration($configuration);
        if (!$urlProvider instanceof UrlProvider) {
            throw new InvalidUrlProviderException('No URL Provider available for given configuration.', 1629296635);
        }

        $apiKey = $configuration->getApiKey();
        foreach ($urlProvider->getUrls() as $url) {
            $inputData = $this->fetchDataFromApi($url, $apiKey);
            $this->parser->parse($inputData);
        }
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