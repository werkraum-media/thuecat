<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Entity\Properties;

class Schedule
{
    protected string $startDate = '';

    protected string $endDate = '';

    protected string $startTime = '';

    protected string $endTime = '';

    protected string $scheduleTimezone = '';

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getScheduleTimezone(): string
    {
        return $this->scheduleTimezone;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setStartDate(string $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setEndDate(string $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @internal for mapping via Symfony component.
     */
    public function setScheduleTimezone(string $scheduleTimezone): void
    {
        $this->scheduleTimezone = $scheduleTimezone;
    }
}
