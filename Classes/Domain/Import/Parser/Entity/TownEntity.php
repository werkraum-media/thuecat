<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

class TownEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_town';
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    public function parse(array $node, string $language, array $translationLanguages = []): void
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
