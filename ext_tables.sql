CREATE TABLE tx_thuecat_import_log_entry (
    type varchar(255) DEFAULT 'savingEntity' NOT NULL,
    severity varchar(16) DEFAULT 'info' NOT NULL,
);


CREATE TABLE tx_thuecat_tourist_attraction (
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

CREATE TABLE tx_thuecat_parking_facility (
    sanitation text,
    other_service text,
    traffic_infrastructure text,
    payment_accepted text,
    distance_to_public_transport text,
);

