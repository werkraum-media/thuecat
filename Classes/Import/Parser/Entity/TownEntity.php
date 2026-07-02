<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Import\Parser\ParserContext;

class TownEntity extends AbstractEntity
{
    public string $table = 'tx_thuecat_town';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    public function parse(array $node, string $language, ParserContext $parserContext, array $translationLanguages = []): void
    {
        $this->translations = [];
        $this->remote_id = $this->getRemoteId($node);

        $localisedFields = [
            'title' => 'schema:name',
            'description' => 'schema:description',
        ];
        foreach ($localisedFields as $field => $jsonldName) {
            $this->$field = $this->extractLocalisedValue($node[$jsonldName] ?? null, $language);
        }

        foreach ($translationLanguages as $code => $sysLanguageUid) {
            foreach ($localisedFields as $field => $jsonldName) {
                $value = $this->extractLocalisedValue($node[$jsonldName] ?? null, $code);
                $this->recordTranslation($field, $value, $sysLanguageUid);
            }
        }

        $this->recordTransient('managedBy', $node['thuecat:managedBy'] ?? null);
    }

    public function handlesTypes(): array
    {
        return ['schema:City'];
    }
}
