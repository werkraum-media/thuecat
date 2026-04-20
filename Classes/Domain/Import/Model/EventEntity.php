<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

class EventEntity implements Entity
{
    private bool $created = false;

    private int $typo3Uid = 0;

    private int $locationUid = 0;

    public function __construct(
        private readonly int $typo3StoragePid,
        private readonly int $typo3SystemLanguageUid,
        private readonly string $remoteId,
        private readonly string $title,
        private readonly string $teaser,
        private readonly string $priceInfo,
        private readonly string $web,
        private readonly string $ticket,
        private readonly string $keywords,
        private readonly string $sourceName = 'thuecat',
        private readonly string $sourceUrl = '',
    ) {
    }

    public function setLocationUid(int $uid): void
    {
        $this->locationUid = $uid;
    }

    public function getTypo3StoragePid(): int
    {
        return $this->typo3StoragePid;
    }

    public function getTypo3DatabaseTableName(): string
    {
        return 'tx_events_domain_model_event';
    }

    public function getTypo3SystemLanguageUid(): int
    {
        return $this->typo3SystemLanguageUid;
    }

    public function isForDefaultLanguage(): bool
    {
        return $this->typo3SystemLanguageUid === 0;
    }

    public function isTranslation(): bool
    {
        return $this->typo3SystemLanguageUid !== 0;
    }

    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    public function getData(): array
    {
        return [
            'title' => $this->title,
            'teaser' => $this->teaser,
            'price_info' => $this->priceInfo,
            'web' => $this->web,
            'ticket' => $this->ticket,
            'keywords' => $this->keywords,
            'global_id' => $this->remoteId,
            'source_name' => $this->sourceName,
            'source_url' => $this->sourceUrl,
            'location' => $this->locationUid,
        ];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setImportedTypo3Uid(int $uid): void
    {
        $this->typo3Uid = $uid;
        $this->created = true;
    }

    public function setExistingTypo3Uid(int $uid): void
    {
        $this->typo3Uid = $uid;
        $this->created = false;
    }

    public function getTypo3Uid(): int
    {
        return $this->typo3Uid;
    }

    public function exists(): bool
    {
        return $this->typo3Uid !== 0;
    }

    public function wasCreated(): bool
    {
        return $this->created;
    }
}
