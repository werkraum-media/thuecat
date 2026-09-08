<?php

declare(strict_types=1);

return [
    'tx_thuecat_tourist_attraction' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Erste Attraktion',
            'description' => 'Die Beschreibung der Attraktion',
            'town' => '1',
            'url' => 'https://example.com/attraction',
            'offers' => '[{"type":"GuidedTourOffer","title":"F\\u00fchrungen","description":"Immer samstags, um 11:15 Uhr findet eine \\u00f6ffentliche F\\u00fchrung durch das Museum statt. Dauer etwa 90 Minuten","prices":[{"title":"Erwachsene","description":"","price":8,"currency":"EUR","rule":"PerPerson"},{"title":"Erm\\u00e4\\u00dfigt","description":"als erm\\u00e4\\u00dfigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner\\/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt","price":5,"currency":"EUR","rule":"PerPerson"}]},{"type":"EntryOffer","title":"Eintritt","description":"Schulklassen und Kitagruppen im Rahmen des Unterrichts: Eintritt frei\\nAn jedem ersten Dienstag im Monat: Eintritt frei","prices":[{"title":"Erm\\u00e4\\u00dfigt","description":"als erm\\u00e4\\u00dfigt gelten schulpflichtige Kinder, Auszubildende, Studierende, Rentner\\/-innen, Menschen mit Behinderungen, Inhaber Sozialausweis der Landeshauptstadt Erfurt","price":5,"currency":"EUR","rule":"PerPerson"},{"title":"Familienkarte","description":"","price":17,"currency":"EUR","rule":"PerGroup"},{"title":"ErfurtCard","description":"","price":14.9,"currency":"EUR","rule":"PerPackage"},{"title":"Erwachsene","description":"","price":8,"currency":"EUR","rule":"PerPerson"}]}]',
            'slogan' => 'Highlight',
            'start_of_construction' => '11. Jh',
            'sanitation' => 'Toilets,DisabledToilets,NappyChangingArea,FamilyAndChildFriendly',
            'other_service' => 'Playground,SeatingPossibilitiesRestArea,SouvenirShop,PlayCornerOrPlayArea',
            'museum_service' => 'MuseumShop,PedagogicalOffer,ZeroInformationMuseumService',
            'architectural_style' => 'ArchitectureOfHomelandSecurity,ArtDeco,ArtNouveau,Baroque,BauhausStyle,Brutalism,Classicism,Constructivism,CriticalRegionalism,Deconstructivism,Expressionism,Functionalism,GothicArt,GothicRevival,HighTechArchitecture,Historicism,InternationalStyle,Minimalism,Modernism,Neoclassicism,Neorenaissance,NewBuilding,NewObjectivity,OrganicConstruction,PostWarModernism,PostmodernAge,Rationalism,Renaissance,Rococo,RomanesquePeriod,ZeroInformationArchitecturalStyle',
            'traffic_infrastructure' => 'BicycleLockersEnumMem,BicycleStandsEnumMem,BicycleStandsBicycleLockersEnumMem,BusParkCoachParkEnumMem,EbikeChargingStationEnumMem,ElectricVehicleCarChargingStationEnumMem,ZeroSpecialTrafficInfrastructure',
            'payment_accepted' => 'AliPay,AmericanExpress,ApplePay,CashPayment,EC,InstantBankTransfer,Invoice,MasterCard,PayPal,Visa',
            'digital_offer' => 'AppForMobileDevices,AudioGuide,AugmentedReality,VideoGuide,VirtuellReality,ZeroDigitalOffer',
            'photography' => 'PhotoLicenceFeeRequired,TakingPicturesPermitted,ZeroPhotography,some free text value for photography',
            'pets_allowed' => 'Tiere sind im Gebäude nicht gestattet, ausgenommen sind Blinden- und Blindenbegleithunde.',
            'available_languages' => 'German,English,French',
            'distance_to_public_transport' => '250:MTR',
            'parking_facility_near_by' => '1,2',
            'accessibility_specification' => '{"accessibilityCertificationStatus":"AccessibilityChecked","accessibilitySearchCriteria":{"facilityAccessibilityDeaf":["AudioInductionLoop","FlashingSignalCallWaitingDoor","SpecialOffersDeafPeople","SpecialOffersHearingImpairment","VisualConfirmationDistressCallElevator"],"facilityAccessibilityMental":["ColoredOrPictorialGuidanceSystem","InformationInEasyLanguage","InformationWithPictogramsOrPictures"],"facilityAccessibilityVisual":["AssistanceDogsWelcome","GuidanceSystemWithFloorIndicators","InformationBrailleOrPrismaticFont","OffersInPictoralLanguage","SpecialOffersBlindPeople","SpecialOffersVisualImpairment","TactileOffers","VisuallyContrastingStepEdges"],"facilityAccessibilityWalking":["AllRoomsStepFreeAccess","EightyCMWidthPassageWays","EntryAidSwimmingPool","GrabRailInShower","HandrailsOnBothSidesOfAllStaircases","HingedGrabRailToilet","LateralAccessibleToilet","MinumumManoeuvringSpaceShower","NinetyCMWidthPassageWays","NursingBed","ParkingPeopleWithDisabilities","SeventyCMWidthPassageWays","ShowerSeat","SpecialOffersWalkingImpairment","SpecialOffersWheelchairUsers","StepFreeAccess","StepFreeShower","ToiletsPeopleWithDisabilities"]},"certificationAccessibilityDeaf":"Full","certificationAccessibilityMental":"None","certificationAccessibilityPartiallyDeaf":"None","certificationAccessibilityPartiallyVisual":"Info","certificationAccessibilityVisual":"None","certificationAccessibilityWalking":"Info","certificationAccessibilityWheelchair":"Info","shortDescriptionAccessibilityAllGenerations":"Deutsche Beschreibung von shortDescriptionAccessibilityAllGenerations","shortDescriptionAccessibilityAllergic":"Deutsche Beschreibung von shortDescriptionAccessibilityAllergic","shortDescriptionAccessibilityDeaf":"Deutsche Beschreibung von shortDescriptionAccessibilityDeaf","shortDescriptionAccessibilityMental":"Deutsche Beschreibung von shortDescriptionAccessibilityMental","shortDescriptionAccessibilityVisual":"Deutsche Beschreibung von shortDescriptionAccessibilityVisual","shortDescriptionAccessibilityWalking":"Deutsche Beschreibung von shortDescriptionAccessibilityWalking"}',
        ],
    ],
    'tx_thuecat_town' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Beispielstadt',
            'description' => 'Die Beschreibung der Stadt',
        ],
    ],
    'tx_thuecat_parking_facility' => [
        0 => [
            'uid' => '1',
            'pid' => '3',
            'title' => 'Parkhaus Domplatz',
        ],
        1 => [
            'uid' => '2',
            'pid' => '3',
            'title' => 'Q-Park Anger 1 Parkhaus',
        ],
    ],
];
