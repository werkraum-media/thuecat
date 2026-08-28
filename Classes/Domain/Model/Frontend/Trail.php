<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Trail extends Base
{
    protected ?FileReference $logo = null;

    protected ?Organisation $managedBy = null;

    /**
     * @var ObjectStorage<TrailWayType>
     */
    protected ObjectStorage $wayTypes;

    /**
     * @var ObjectStorage<TrailCondition>
     */
    protected ObjectStorage $conditions;

    protected ?TrailLocation $startLocation = null;

    protected ?TrailLocation $endLocation = null;

    protected string $shortDescription = '';

    protected string $directions = '';

    protected string $gettingThere = '';

    protected string $parking = '';

    protected string $publicTransit = '';

    protected string $safetyGuidelines = '';

    protected string $equipment = '';

    protected string $additionalInformation = '';

    protected string $tip = '';

    protected string $openingStatus = '';

    /** Bitmask; see TrailSeason for the member each bit stands for. */
    protected int $season = 0;

    protected string $elevationProfile = '';

    protected string $elevationProfileFallBack = '';

    protected string $routeLine = '';

    protected string $gpxUrl = '';

    protected string $distance = '';

    protected string $distanceUnit = '';

    protected string $duration = '';

    protected string $durationUnit = '';

    protected string $exerciseType = '';

    protected string $minAltitude = '';

    protected string $maxAltitude = '';

    protected string $ascentElevation = '';

    protected string $descentElevation = '';

    protected string $ratingLandscape = '';

    protected string $ratingLandscapeMin = '';

    protected string $ratingLandscapeMax = '';

    protected string $ratingCondition = '';

    protected string $ratingConditionMin = '';

    protected string $ratingConditionMax = '';

    protected string $ratingDifficulty = '';

    protected string $ratingDifficultyMin = '';

    protected string $ratingDifficultyMax = '';

    protected string $ratingQualityOfExperience = '';

    protected string $ratingQualityOfExperienceMin = '';

    protected string $ratingQualityOfExperienceMax = '';

    protected string $ratingTechnique = '';

    protected string $ratingTechniqueMin = '';

    protected string $ratingTechniqueMax = '';

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->wayTypes = new ObjectStorage();
        $this->conditions = new ObjectStorage();
    }

    public function getLogo(): ?FileReference
    {
        return $this->logo;
    }

    public function getManagedBy(): ?Organisation
    {
        return $this->managedBy;
    }

    /**
     * @return ObjectStorage<TrailWayType>
     */
    public function getWayTypes(): ObjectStorage
    {
        return $this->wayTypes;
    }

    /**
     * @return ObjectStorage<TrailCondition>
     */
    public function getConditions(): ObjectStorage
    {
        return $this->conditions;
    }

    public function getStartLocation(): ?TrailLocation
    {
        return $this->startLocation;
    }

    public function getEndLocation(): ?TrailLocation
    {
        return $this->endLocation;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getDirections(): string
    {
        return $this->directions;
    }

    public function getGettingThere(): string
    {
        return $this->gettingThere;
    }

    public function getParking(): string
    {
        return $this->parking;
    }

    public function getPublicTransit(): string
    {
        return $this->publicTransit;
    }

    public function getSafetyGuidelines(): string
    {
        return $this->safetyGuidelines;
    }

    public function getEquipment(): string
    {
        return $this->equipment;
    }

    public function getAdditionalInformation(): string
    {
        return $this->additionalInformation;
    }

    public function getTip(): string
    {
        return $this->tip;
    }

    public function getOpeningStatus(): string
    {
        return $this->openingStatus;
    }

    public function getSeason(): int
    {
        return $this->season;
    }

    public function getElevationProfile(): string
    {
        return $this->elevationProfile;
    }

    public function getElevationProfileFallBack(): string
    {
        return $this->elevationProfileFallBack;
    }

    public function getRouteLine(): string
    {
        return $this->routeLine;
    }

    public function getGpxUrl(): string
    {
        return $this->gpxUrl;
    }

    public function getDistance(): string
    {
        return $this->distance;
    }

    public function getDistanceUnit(): string
    {
        return $this->distanceUnit;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function getDurationUnit(): string
    {
        return $this->durationUnit;
    }

    public function getExerciseType(): string
    {
        return $this->exerciseType;
    }

    public function getMinAltitude(): string
    {
        return $this->minAltitude;
    }

    public function getMaxAltitude(): string
    {
        return $this->maxAltitude;
    }

    public function getAscentElevation(): string
    {
        return $this->ascentElevation;
    }

    public function getDescentElevation(): string
    {
        return $this->descentElevation;
    }

    public function getRatingLandscape(): string
    {
        return $this->ratingLandscape;
    }

    public function getRatingLandscapeMin(): string
    {
        return $this->ratingLandscapeMin;
    }

    public function getRatingLandscapeMax(): string
    {
        return $this->ratingLandscapeMax;
    }

    public function getRatingCondition(): string
    {
        return $this->ratingCondition;
    }

    public function getRatingConditionMin(): string
    {
        return $this->ratingConditionMin;
    }

    public function getRatingConditionMax(): string
    {
        return $this->ratingConditionMax;
    }

    public function getRatingDifficulty(): string
    {
        return $this->ratingDifficulty;
    }

    public function getRatingDifficultyMin(): string
    {
        return $this->ratingDifficultyMin;
    }

    public function getRatingDifficultyMax(): string
    {
        return $this->ratingDifficultyMax;
    }

    public function getRatingQualityOfExperience(): string
    {
        return $this->ratingQualityOfExperience;
    }

    public function getRatingQualityOfExperienceMin(): string
    {
        return $this->ratingQualityOfExperienceMin;
    }

    public function getRatingQualityOfExperienceMax(): string
    {
        return $this->ratingQualityOfExperienceMax;
    }

    public function getRatingTechnique(): string
    {
        return $this->ratingTechnique;
    }

    public function getRatingTechniqueMin(): string
    {
        return $this->ratingTechniqueMin;
    }

    public function getRatingTechniqueMax(): string
    {
        return $this->ratingTechniqueMax;
    }
}
