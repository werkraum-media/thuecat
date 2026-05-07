<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\Events;

use WerkraumMedia\ThueCat\Domain\Import\Importer\FetchData;
use WerkraumMedia\ThueCat\Domain\Import\Parser\Entity\AbstractEntity;
use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

abstract class AbstractEventsEntity extends AbstractEntity
{
    protected string $source_name = '';
    protected string $source_url = '';

    protected int $thuecat_import_configuration = 0;

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->source_name = 'thuecat';  // intentionally hardcoded, to distinguish from destionation.one direct import via ext:events
        $this->source_url = $parserContext->apiDomain !== '' ? $parserContext->apiDomain : FetchData::DEFAULT_API_DOMAIN;

        $this->thuecat_import_configuration = $parserContext->importConfigurationUid;
    }
}
