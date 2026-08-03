<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Parser\Entity\Events\Support;

use WerkraumMedia\ThueCat\Import\Parser\Entity\Category\SysCategoryMapper;

// Maps event @type URIs to the `categories` field. Grows as more mappings are requested.
class EventCategoryMapper extends SysCategoryMapper
{
    public function kind(): string
    {
        return 'categories';
    }

    public function sourcePrefix(): string
    {
        return 'type:';
    }

    protected function titleMap(): array
    {
        return [
            'thuecat:ActivInNature' => 'Aktiv in der Natur',
            'schema:ExhibitionEvent' => 'Ausstellung',
            'thuecat:BrassMusic' => 'Blasmusik',
            'schema:ComedyEvent' => 'Comedy',
            'schema:FoodEvent' => 'Essen und Trinken',
            'thuecat:FamilyEvent' => 'Familienveranstaltung',
            'thuecat:CelebrationEvent' => 'Feier',
            'thuecat:PartyFunNightlife' => 'Feiern / Spaß / Nightlife',
            'schema:Festival' => 'Festival',
            'schema:ScreeningEvent' => 'Film / Multimedia',
            'thuecat:GuidedTour' => 'Führung',
            'thuecat:GuidedTourEvent' => 'Führung und Stadtrundgang',
            'thuecat:SociabilityEvent' => 'Geselligkeit',
            'thuecat:Funfairs' => 'Jahrmarkt und Rummel',
            'thuecat:ComedyAndCabaretEvent' => 'Kabarett',
            'thuecat:CarnivalEvent' => 'Karneval und Fasching',
            'thuecat:ChildrenAndYoungPeopleEvent' => 'Kinder- und Jugendveranstaltung',
            'schema:ChildrensEvent' => 'Kinderveranstaltung',
            'thuecat:ClassicalMusic' => 'Klassische Musik',
            'thuecat:CultureEvent' => 'Kulturveranstaltung',
            'thuecat:ArtFestival' => 'Kunstfestival',
            'schema:LiteraryEvent' => 'Literatur',
            'schema:SaleEvent' => 'Markt, Fest und Umzug',
            'schema:BusinessEvent' => 'Messe / Tagung / Kongress',
            'schema:MusicEvent' => 'Musik',
            'thuecat:OperaEvent' => 'Oper',
            'thuecat:Easter' => 'Ostern',
            'schema:SocialEvent' => 'Politik und Gesellschaft',
            'thuecat:WalkaboutEvent' => 'Rundgang',
            'thuecat:SeasonHolidays' => 'Saison und Feiertage',
            'thuecat:ShowDemonstration' => 'Schauvorführung',
            'thuecat:SeniorsEvent' => 'Seniorenveranstaltung',
            'thuecat:ShowAndDanceEvent' => 'Show und Tanz',
            'schema:SportsEvent' => 'Sport',
            'thuecat:CityTour' => 'Stadtrundgang',
            'schema:TheaterEvent' => 'Theaterveranstaltung',
            'schema:EventSeries' => 'Veranstaltungsserie',
            'thuecat:Lectures' => 'Vortrag',
            'thuecat:Christmas' => 'Weihnachten',
            'thuecat:WellnessHealth' => 'Wellness und Gesundheit',
            'schema:EducationEvent' => 'Wissenschaft und Bildung',
            'thuecat:Workshop' => 'Workshop',
            'thuecat:ReligiousEvent' => 'religiöse Veranstaltung',
        ];
    }

    protected function ignoredValues(): array
    {
        return [
            'schema:Thing',
            'schema:Intangible',
            'schema:Event',
            // Supertype of schema:EventSeries, which is mapped.
            'schema:Series',
            'dcmitype:Event',
            'ttgds:Event',
        ];
    }
}
