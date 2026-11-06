<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Domain\Model\Frontend;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class MeetingVenue extends TouristAttraction
{
    protected string $meetingFacilities = '';

    protected string $gastro = '';

    protected string $servesCuisine = '';

    protected string $entrance = '';

    protected string $guidedTour = '';

    protected string $monument = '';

    protected string $ensemble = '';

    protected string $exhibitionObjects = '';

    protected string $sportsEquipment = '';

    protected string $amenityFeatures = '';

    protected string $venueType = '';

    protected int $maximumAttendeeCapacity = 0;

    protected int $distanceAirport = 0;

    protected int $distanceCityCenter = 0;

    protected int $distanceFair = 0;

    protected int $distanceFreeway = 0;

    protected int $distanceIceStation = 0;

    protected string $priceRange = '';

    protected string $starRating = '';

    protected string $checkinTime = '';

    protected string $checkoutTime = '';

    protected string $smokingAllowed = '';

    protected string $isRuin = '';

    protected string $openAirMuseum = '';

    protected string $bookable = '';

    protected string $hasDriveThroughService = '';

    protected string $beerGarden = '';

    protected string $catering = '';

    protected string $deliveryService = '';

    protected string $hotCuisineThroughout = '';

    protected string $lateKitchen = '';

    protected string $selfService = '';

    protected string $takeAway = '';

    protected string $acceptsReservations = '';

    protected string $typicalAgeRange = '';

    protected string $endOfConstruction = '';

    protected string $descriptionCulturePortal = '';

    protected string $descriptionLodgingMeeting = '';

    protected string $certifications = '';

    protected string $hasMap = '';

    protected string $tourBookingPage = '';

    protected string $identifier = '';

    protected string $sameAs = '';

    protected string $subjectOf = '';

    protected string $touristTypes = '';

    protected string $whoWasHere = '';

    protected string $architects = '';

    protected int $numberOfRooms = 0;

    protected int $singleRooms = 0;

    protected int $doubleRooms = 0;

    protected int $twinRooms = 0;

    protected int $suites = 0;

    protected int $seatsInside = 0;

    protected int $seatsOutside = 0;

    protected int $sizeOfArea = 0;

    protected ?FileReference $logo = null;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $subjectOfMedia;

    protected ?Organisation $operatedBy = null;

    /**
     * @var ObjectStorage<Trail>
     */
    protected ObjectStorage $geoTouchesTrail;

    /**
     * @var ObjectStorage<TouristAttraction>
     */
    protected ObjectStorage $managedObjects;

    /**
     * @var ObjectStorage<ParkingFacility>
     */
    protected ObjectStorage $parkingFacilitiesInline;

    /**
     * @var ObjectStorage<MeetingRoom>
     */
    protected ObjectStorage $meetingRooms;

    public function initializeObject(): void
    {
        parent::initializeObject();
        $this->meetingRooms = new ObjectStorage();
        $this->subjectOfMedia = new ObjectStorage();
        $this->geoTouchesTrail = new ObjectStorage();
        $this->managedObjects = new ObjectStorage();
        $this->parkingFacilitiesInline = new ObjectStorage();
    }

    public function getAdditionalType(): string
    {
        return match ($this->venueType) {
            'meeting_congress_hotel' => 'https://thuecat.org/ontology/thuecat/1.0/MeetingCongressHotel',
            'meeting_congress' => 'https://thuecat.org/ontology/thuecat/1.0/MeetingCongress',
            default => '',
        };
    }

    public function getMaximumAttendeeCapacity(): int
    {
        return $this->maximumAttendeeCapacity;
    }

    /**
     * Named apart from the property: Extbase types a property from its same-named getter, and an array
     * typed property is never mapped.
     *
     * @return list<string>
     */
    public function getMeetingFacilityMembers(): array
    {
        return $this->membersOf($this->meetingFacilities);
    }

    /**
     * @return list<string>
     */
    public function getGastroMembers(): array
    {
        return $this->membersOf($this->gastro);
    }

    /**
     * @return list<string>
     */
    public function getServesCuisineMembers(): array
    {
        return $this->membersOf($this->servesCuisine);
    }

    /**
     * @return list<string>
     */
    public function getEntranceMembers(): array
    {
        return $this->membersOf($this->entrance);
    }

    /**
     * @return list<string>
     */
    public function getGuidedTourMembers(): array
    {
        return $this->membersOf($this->guidedTour);
    }

    /**
     * @return list<string>
     */
    public function getMonumentMembers(): array
    {
        return $this->membersOf($this->monument);
    }

    /**
     * @return list<string>
     */
    public function getEnsembleMembers(): array
    {
        return $this->membersOf($this->ensemble);
    }

    /**
     * @return list<string>
     */
    public function getExhibitionObjectMembers(): array
    {
        return $this->membersOf($this->exhibitionObjects);
    }

    /**
     * @return list<string>
     */
    public function getSportsEquipmentMembers(): array
    {
        return $this->membersOf($this->sportsEquipment);
    }

    /**
     * @return list<string>
     */
    public function getAmenityFeatureMembers(): array
    {
        return $this->membersOf($this->amenityFeatures);
    }

    public function getVenueType(): string
    {
        return $this->venueType;
    }

    public function getPriceRange(): string
    {
        return $this->priceRange;
    }

    public function getStarRating(): string
    {
        return $this->starRating;
    }

    public function getCheckinTime(): string
    {
        return $this->checkinTime;
    }

    public function getCheckoutTime(): string
    {
        return $this->checkoutTime;
    }

    public function getSmokingAllowed(): string
    {
        return $this->smokingAllowed;
    }

    public function getIsRuin(): string
    {
        return $this->isRuin;
    }

    public function getOpenAirMuseum(): string
    {
        return $this->openAirMuseum;
    }

    public function getBookable(): string
    {
        return $this->bookable;
    }

    public function getHasDriveThroughService(): string
    {
        return $this->hasDriveThroughService;
    }

    public function getBeerGarden(): string
    {
        return $this->beerGarden;
    }

    public function getCatering(): string
    {
        return $this->catering;
    }

    public function getDeliveryService(): string
    {
        return $this->deliveryService;
    }

    public function getHotCuisineThroughout(): string
    {
        return $this->hotCuisineThroughout;
    }

    public function getLateKitchen(): string
    {
        return $this->lateKitchen;
    }

    public function getSelfService(): string
    {
        return $this->selfService;
    }

    public function getTakeAway(): string
    {
        return $this->takeAway;
    }

    public function getAcceptsReservations(): string
    {
        return $this->acceptsReservations;
    }

    public function getTypicalAgeRange(): string
    {
        return $this->typicalAgeRange;
    }

    public function getEndOfConstruction(): string
    {
        return $this->endOfConstruction;
    }

    public function getDescriptionCulturePortal(): string
    {
        return $this->descriptionCulturePortal;
    }

    public function getDescriptionLodgingMeeting(): string
    {
        return $this->descriptionLodgingMeeting;
    }

    public function getNumberOfRooms(): int
    {
        return $this->numberOfRooms;
    }

    public function getSingleRooms(): int
    {
        return $this->singleRooms;
    }

    public function getDoubleRooms(): int
    {
        return $this->doubleRooms;
    }

    public function getTwinRooms(): int
    {
        return $this->twinRooms;
    }

    public function getSuites(): int
    {
        return $this->suites;
    }

    public function getSeatsInside(): int
    {
        return $this->seatsInside;
    }

    public function getSeatsOutside(): int
    {
        return $this->seatsOutside;
    }

    public function getSizeOfArea(): int
    {
        return $this->sizeOfArea;
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getCertificationMembers(): array
    {
        return $this->linesOf($this->certifications);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getHasMapMembers(): array
    {
        return $this->linesOf($this->hasMap);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getTourBookingPageMembers(): array
    {
        return $this->linesOf($this->tourBookingPage);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getIdentifierMembers(): array
    {
        return $this->linesOf($this->identifier);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getSameAsMembers(): array
    {
        return $this->linesOf($this->sameAs);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getSubjectOfMembers(): array
    {
        return $this->linesOf($this->subjectOf);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getTouristTypeMembers(): array
    {
        return $this->linesOf($this->touristTypes);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getWhoWasHereMembers(): array
    {
        return $this->linesOf($this->whoWasHere);
    }

    /**
     * One entry per stored line.
     *
     * @return list<string>
     */
    public function getArchitectMembers(): array
    {
        return $this->linesOf($this->architects);
    }

    public function getLogo(): ?FileReference
    {
        return $this->logo;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getSubjectOfMedia(): ObjectStorage
    {
        return $this->subjectOfMedia;
    }

    public function getOperatedBy(): ?Organisation
    {
        return $this->operatedBy;
    }

    /**
     * @return ObjectStorage<Trail>
     */
    public function getGeoTouchesTrail(): ObjectStorage
    {
        return $this->geoTouchesTrail;
    }

    /**
     * @return ObjectStorage<TouristAttraction>
     */
    public function getManagedObjects(): ObjectStorage
    {
        return $this->managedObjects;
    }

    /**
     * @return ObjectStorage<ParkingFacility>
     */
    public function getParkingFacilitiesInline(): ObjectStorage
    {
        return $this->parkingFacilitiesInline;
    }

    /**
     * Delivered distances only, keyed by destination.
     *
     * @return array<string, array{value: float|int, unit: 'km'|'m'}>
     */
    public function getDistances(): array
    {
        $distances = [];
        foreach ([
            'airport' => $this->distanceAirport,
            'cityCenter' => $this->distanceCityCenter,
            'fair' => $this->distanceFair,
            'freeway' => $this->distanceFreeway,
            'iceStation' => $this->distanceIceStation,
        ] as $destination => $metres) {
            if ($metres === 0) {
                continue;
            }
            $distances[$destination] = $metres >= 1000
                ? ['value' => $metres / 1000.0, 'unit' => 'km']
                : ['value' => $metres, 'unit' => 'm'];
        }

        return $distances;
    }

    /**
     * @return ObjectStorage<MeetingRoom>
     */
    public function getMeetingRooms(): ObjectStorage
    {
        return $this->meetingRooms;
    }

    /**
     * @return list<MeetingRoom>
     */
    public function getMeetingRoomTable(): array
    {
        // Rooms the venue names keep their venue position, so they count as seen before the walk starts.
        $seen = [];
        foreach ($this->meetingRooms as $room) {
            $seen[(int)$room->getUid()] = true;
        }

        $table = [];
        foreach ($this->meetingRooms as $room) {
            $table[] = $room;
            $this->appendRoomsReachedOnlyThrough($room, $table, $seen);
        }

        return $table;
    }

    /**
     * @param list<MeetingRoom> $table
     * @param array<int, true> $seen
     */
    protected function appendRoomsReachedOnlyThrough(MeetingRoom $room, array &$table, array &$seen): void
    {
        foreach ($room->getContainedRooms() as $containedRoom) {
            $uid = (int)$containedRoom->getUid();
            if (isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $table[] = $containedRoom;
            $this->appendRoomsReachedOnlyThrough($containedRoom, $table, $seen);
        }
    }

    /**
     * @return list<string>
     */
    protected function membersOf(string $commaList): array
    {
        return array_values(array_filter(explode(',', $commaList)));
    }

    /**
     * @return list<string>
     */
    protected function linesOf(string $text): array
    {
        return array_values(array_filter(array_map(trim(...), explode("\n", $text)), static fn (string $line): bool => $line !== ''));
    }
}
