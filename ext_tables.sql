CREATE TABLE tx_thuecat_import_log_entry (
    type varchar(255) DEFAULT 'savingEntity' NOT NULL,
    severity varchar(16) DEFAULT 'info' NOT NULL,
);


CREATE TABLE tx_thuecat_tourist_attraction (
    type varchar(255) DEFAULT 'attraction' NOT NULL,
    meeting_facilities text,
    gastro text,
    serves_cuisine text,
    entrance text,
    guided_tour text,
    monument text,
    ensemble text,
    exhibition_objects text,
    sports_equipment text,
    amenity_features text,
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

-- Room equipment lists exceed the varchar(255) TCA derives for an input column (413 characters observed).
CREATE TABLE tx_thuecat_meeting_room (
    url text,
    facilities text,
    amenity_features text,
);

-- manages_* / tourist_information: reverse views of type none, which core gives no column. Kept with
-- the definition their former inline fields had, as the form reads every shown field from the row.
CREATE TABLE tx_thuecat_organisation (
    manages_towns int(10) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_information int(10) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_attraction int(10) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_thuecat_town (
    tourist_information int(10) unsigned DEFAULT '0' NOT NULL,
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


-- A queued removal of imported roots; written by the removal module and its message handlers, not edited in forms
CREATE TABLE tx_thuecat_removal_job (
    uid int(10) unsigned NOT NULL auto_increment,
    crdate int(10) unsigned DEFAULT '0' NOT NULL,
    progressed_at int(10) unsigned DEFAULT '0' NOT NULL,
    backend_user int(10) unsigned DEFAULT '0' NOT NULL,
    status varchar(16) DEFAULT '' NOT NULL,
    root_table varchar(255) DEFAULT '' NOT NULL,
    scope varchar(16) DEFAULT '' NOT NULL,
    scope_page int(10) unsigned DEFAULT '0' NOT NULL,
    origin_configuration_uid int(10) unsigned DEFAULT '0' NOT NULL,
    remove_files tinyint(1) unsigned DEFAULT '0' NOT NULL,
    update_reference_index tinyint(1) unsigned DEFAULT '0' NOT NULL,
    removed_roots int(10) unsigned DEFAULT '0' NOT NULL,
    skipped_roots int(10) unsigned DEFAULT '0' NOT NULL,
    removed_relations int(10) unsigned DEFAULT '0' NOT NULL,
    removed_files int(10) unsigned DEFAULT '0' NOT NULL,
    errors int(10) unsigned DEFAULT '0' NOT NULL,
    error_log mediumtext,

    PRIMARY KEY (uid)
);

CREATE TABLE tx_thuecat_import_job (
    uid int(10) unsigned NOT NULL auto_increment,
    crdate int(10) unsigned DEFAULT '0' NOT NULL,
    progressed_at int(10) unsigned DEFAULT '0' NOT NULL,
    configuration int(10) unsigned DEFAULT '0' NOT NULL,
    job_trigger varchar(16) DEFAULT '' NOT NULL,
    backend_user int(10) unsigned DEFAULT '0' NOT NULL,
    skip_media tinyint(1) unsigned DEFAULT '0' NOT NULL,
    bypass_cache tinyint(1) unsigned DEFAULT '0' NOT NULL,
    status varchar(16) DEFAULT '' NOT NULL,
    started_at int(10) unsigned DEFAULT '0' NOT NULL,
    ended_at int(10) unsigned DEFAULT '0' NOT NULL,
    done_items int(10) unsigned DEFAULT '0' NOT NULL,
    failed_items int(10) unsigned DEFAULT '0' NOT NULL,
    skipped_items int(10) unsigned DEFAULT '0' NOT NULL,
    import_log int(10) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY configuration (configuration, status)
);

CREATE TABLE tx_thuecat_import_job_item (
    uid int(10) unsigned NOT NULL auto_increment,
    crdate int(10) unsigned DEFAULT '0' NOT NULL,
    job int(10) unsigned DEFAULT '0' NOT NULL,
    target varchar(2048) DEFAULT '' NOT NULL,
    action varchar(16) DEFAULT '' NOT NULL,
    position int(10) unsigned DEFAULT '0' NOT NULL,
    state varchar(16) DEFAULT '' NOT NULL,
    attempts int(10) unsigned DEFAULT '0' NOT NULL,
    successor_sent tinyint(1) unsigned DEFAULT '0' NOT NULL,
    duration int(10) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY job (job, state, position)
);
