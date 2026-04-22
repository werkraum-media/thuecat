<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Parser\ParserContext;

class TownEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_town';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    public function configure(array $node, ParserContext $context): void
    {
        $language = $context->language;

        $this->remote_id = $this->getRemoteId($node);
        // Text fields (schema:name, schema:description, …) carry one entry per
        // locale; pick the one matching the site's language so the default row
        // holds the German (or configured) strings. Overlay rows for other
        // languages are the later localisation pipeline's job.
        $this->title = $this->extractLocalisedValue($node['schema:name'] ?? null, $language);
        $this->description = $this->extractLocalisedValue($node['schema:description'] ?? null, $language);

        $this->recordTransient('managedBy', $node['thuecat:managedBy'] ?? null);
    }

    public function handlesTypes(): array
    {
        return ['schema:City'];
    }
}
