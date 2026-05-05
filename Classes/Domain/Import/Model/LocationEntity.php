<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Model;

class LocationEntity implements Entity
{
    private bool $created = false;

    private int $typo3Uid = 0;

    public function __construct(
        private readonly int $typo3StoragePid,
        private readonly int $typo3SystemLanguageUid,
        private readonly string $name,
        private readonly string $street,
        private readonly string $zip,
        private readonly string $city,
        private readonly string $country,
        private readonly string $phone,
        private readonly string $latitude,
        private readonly string $longitude,
        private readonly string $globalId,
    ) {
    }

    public function getTypo3StoragePid(): int
    {
        return $this->typo3StoragePid;
    }

    public function getTypo3DatabaseTableName(): string
    {
        return 'tx_events_domain_model_location';
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
        return $this->globalId;
    }

    public function getData(): array
    {
        return [
            'name' => $this->name,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'global_id' => $this->globalId,
        ];
    }

    public function getTypo3Uid(): int
    {
        return $this->typo3Uid;
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

    public function exists(): bool
    {
        return $this->typo3Uid !== 0;
    }

    public function wasCreated(): bool
    {
        return $this->created;
    }
}
