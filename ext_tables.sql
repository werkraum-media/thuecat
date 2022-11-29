CREATE TABLE pages (
    tx_thuecat_flexform text,
);

CREATE TABLE tx_thuecat_import_configuration (
    title varchar(255) DEFAULT '' NOT NULL,
    type varchar(255) DEFAULT '' NOT NULL,
    configuration text,
    logs int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_thuecat_import_log (
    configuration int(11) unsigned DEFAULT '0' NOT NULL,
    log_entries int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_thuecat_import_log_entry (
    import_log int(11) unsigned DEFAULT '0' NOT NULL,
    record_uid int(11) unsigned DEFAULT '0' NOT NULL,
    table_name varchar(255) DEFAULT '' NOT NULL,
    insertion TINYINT(1) unsigned DEFAULT '0' NOT NULL,
    errors text,
);

CREATE TABLE tx_thuecat_organisation (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    manages_towns int(11) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_information int(11) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_attraction int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_thuecat_town (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    tourist_information int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
);

CREATE TABLE tx_thuecat_tourist_information (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    town int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
);

CREATE TABLE tx_thuecat_tourist_attraction (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    town int(11) unsigned DEFAULT '0' NOT NULL,
    parking_facility_near_by varchar(255) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    opening_hours text,
    special_opening_hours text,
    address text,
    url text,
    media text,
    offers text,
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
    accessibility_specification text,
);

CREATE TABLE tx_thuecat_parking_facility (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    town int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    opening_hours text,
    special_opening_hours text,
    address text,
    media text,
    offers text,
    sanitation text,
    other_service text,
    traffic_infrastructure text,
    payment_accepted text,
    distance_to_public_transport text,
);
