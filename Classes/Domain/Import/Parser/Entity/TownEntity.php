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
        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLanguageValue($node['schema:name'] ?? null);
        $this->description = $this->extractLanguageValue($node['schema:description'] ?? null);

        $this->recordTransient('managedBy', $node['thuecat:managedBy'] ?? null);
    }

    public function handlesTypes(): array
    {
        return ['schema:City'];
    }
}
