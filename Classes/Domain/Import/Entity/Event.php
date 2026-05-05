<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\EventLocation;
use WerkraumMedia\ThueCat\Domain\Import\Entity\Properties\Schedule;

class Event extends Minimum implements MapsToType
{
    protected ?Schedule $eventSchedule = null;

    protected ?EventLocation $location = null;

    /**
     * Inline organizer — may be a Place or Organization node without its own @id.
     */
    protected ?EventLocation $organizer = null;

    protected string $priceInfo = '';

    protected string $web = '';

    protected string $ticket = '';

    protected string $keywords = '';

    public function getEventSchedule(): ?Schedule
    {
        return $this->eventSchedule;
    }

    public function getLocation(): ?EventLocation
    {
        return $this->location;
    }

    public function getOrganizer(): ?EventLocation
    {
        return $this->organizer;
    }

    public function getPriceInfo(): string
    {
        return $this->priceInfo;
    }

    public function getWeb(): string
    {
        return $this->web;
    }

    public function getTicket(): string
    {
        return $this->ticket;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setEventSchedule(Schedule $eventSchedule): void
    {
        $this->eventSchedule = $eventSchedule;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setLocation(EventLocation $location): void
    {
        $this->location = $location;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setOrganizer(EventLocation $organizer): void
    {
        $this->organizer = $organizer;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setPriceInfo(string $priceInfo): void
    {
        $this->priceInfo = $priceInfo;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setWeb(string $web): void
    {
        $this->web = $web;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setTicket(string $ticket): void
    {
        $this->ticket = $ticket;
    }

    /**
     * @internal for mapping via Symfony component.
     *
     * @param string[] $keywords
     */
    public function setKeywords(array $keywords): void
    {
        $this->keywords = implode(', ', array_filter($keywords));
    }

    public static function getSupportedTypes(): array
    {
        return [
            'schema:Event',
        ];
    }
}
