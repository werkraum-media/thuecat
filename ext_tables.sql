CREATE TABLE tx_thuecat_import_log_entry (
    type varchar(255) DEFAULT 'savingEntity' NOT NULL,
    severity varchar(16) DEFAULT 'info' NOT NULL,
);


-- hosts_events / manages_event: MM relations, so core derives an int count column. Kept as text
-- until thuecat_eventRelationsToMm has run everywhere, so the schema update cannot turn the stored
-- uid lists into int before the wizard reads them.
CREATE TABLE tx_thuecat_tourist_attraction (
    hosts_events text,
    url text,
    slogan text,
    start_of_construction text,
    sanitation text,
    other_service text,
    museum_service text,
    architectural_style text,
    traffic_infrastructure text,
    payment_accepted text,
    digital_offer text,
    photography text,
    pets_allowed text,
    is_accessible_for_free text,
    public_access text,
    available_languages text,
    distance_to_public_transport text,
);

CREATE TABLE tx_thuecat_organisation (
    hosts_events text,
    manages_event text,
);

CREATE TABLE tx_thuecat_town (
    hosts_events text,
);

CREATE TABLE tx_thuecat_tourist_information (
    hosts_events text,
);

CREATE TABLE tx_thuecat_parking_facility (
    sanitation text,
    other_service text,
    traffic_infrastructure text,
    payment_accepted text,
    distance_to_public_transport text,
);

CREATE TABLE tx_thuecat_trail (
    route_line mediumtext,
);

-- Coordinates keep the source's full precision as text
CREATE TABLE tx_thuecat_address (
    latitude varchar(32) DEFAULT '' NOT NULL,
    longitude varchar(32) DEFAULT '' NOT NULL,
);

