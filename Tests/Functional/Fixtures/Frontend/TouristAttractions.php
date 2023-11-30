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
            'media' => '[{"mainImage":true,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5159216\\/Preview-1280x0\\/image","copyrightYear":2016,"author":"Image Author","license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":""}},{"mainImage":false,"type":"image","title":"Erfurt-Dom-und-Severikirche.jpg","description":"Sicht auf Dom St. Marien, St. Severikirche sowie die davor liegenden Klostergeb\\u00e4ude und einem Ausschnitt des Biergartens umgeben von einem d\\u00e4mmerungsverf\\u00e4rten Himmel","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5159186\\/Preview-1280x0\\/image","copyrightYear":2020,"license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":""}},{"mainImage":false,"type":"image","title":"Erfurt-Dom und Severikirche-beleuchtet.jpg","description":"","url":"https:\\/\\/cms.thuecat.org\\/o\\/adaptive-media\\/image\\/5159216\\/Preview-1280x0\\/image","copyrightYear":2016,"license":{"type":"https:\\/\\/creativecommons.org\\/licenses\\/by\\/4.0\\/","author":""}}]',
            'opening_hours' => '[{"opens":"09:30:00","closes":"18:00:00","from":{"date":"2021-05-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2021-10-31 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Saturday","Friday","Thursday","Tuesday","Monday","Wednesday"]},{"opens":"13:00:00","closes":"18:00:00","from":{"date":"2021-05-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2021-10-31 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Sunday"]},{"opens":"09:30:00","closes":"17:00:00","from":{"date":"2021-11-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2022-04-30 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Saturday","Friday","Thursday","Tuesday","Monday","Wednesday"]},{"opens":"13:00:00","closes":"17:00:00","from":{"date":"2021-11-01 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"through":{"date":"2022-04-30 00:00:00.000000","timezone_type":3,"timezone":"UTC"},"daysOfWeek":["Sunday","PublicHolidays"]}]',
            'address' => '{"street":"Beispielstraße 1a","zip":"99084","city":"Beispielstadt","email":"example@example.com","phone":"(0)30 23125 000","fax":"","geo":{"latitude":50.975955358589545,"longitude":11.023667024961856}}',
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
            'address' => '{"street":"Bechtheimer Str. 1","zip":"99084","city":"Erfurt","email":"info@stadtwerke-erfurt.de","phone":"+49 361 5640","fax":"","geo":{"latitude":50.977648905044,"longitude":11.022127985954299}}',
        ],
        1 => [
            'uid' => '2',
            'pid' => '3',
            'title' => 'Q-Park Anger 1 Parkhaus',
            'address' => '{"street":"Anger 1","zip":"99084","city":"Erfurt","email":"servicecenter@q-park.de","phone":"+49 218 18190290","fax":"","geo":{"latitude":50.977999330565794,"longitude":11.037503264052475}}',
        ],
    ],
];
