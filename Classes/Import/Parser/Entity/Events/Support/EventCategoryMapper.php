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
            'schema:FoodEvent' => 'Essen und Trinken',
            'thuecat:FamilyEvent' => 'Familienveranstaltung',
            'thuecat:PartyFunNightlife' => 'Feiern / Spaß / Nightlife',
            'schema:Festival' => 'Festival',
            'schema:ScreeningEvent' => 'Film / Multimedia',
            'thuecat:GuidedTourEvent' => 'Führung und Stadtrundgang',
            'thuecat:SociabilityEvent' => 'Geselligkeit',
            'schema:ChildrensEvent' => 'Kinderveranstaltung',
            'thuecat:CultureEvent' => 'Kulturveranstaltung',
            'schema:SaleEvent' => 'Markt, Fest und Umzug',
            'schema:BusinessEvent' => 'Messe / Tagung / Kongress',
            'schema:MusicEvent' => 'Musik',
            'thuecat:Easter' => 'Ostern',
            'schema:SocialEvent' => 'Politik und Gesellschaft',
            'thuecat:SeasonHolidays' => 'Saison und Feiertage',
            'thuecat:ShowDemonstration' => 'Schauvorführung',
            'schema:SportsEvent' => 'Sport',
            'schema:TheaterEvent' => 'Theaterveranstaltung',
            'schema:EventSeries' => 'Veranstaltungsserie',
            'thuecat:Christmas' => 'Weihnachten',
            'thuecat:WellnessHealth' => 'Wellness und Gesundheit',
            'schema:EducationEvent' => 'Wissenschaft und Bildung',
            'thuecat:ReligiousEvent' => 'religiöse Veranstaltung',
            // No title yet; the URI stands in.
            'thuecat:ComedyAndCabaretEvent' => 'thuecat:ComedyAndCabaretEvent',
            'thuecat:ShowAndDanceEvent' => 'thuecat:ShowAndDanceEvent',
        ];
    }

    protected function ignoredValues(): array
    {
        return [
            'schema:Thing',
            'schema:Event',
            'dcmitype:Event',
            'ttgds:Event',
        ];
    }
}
