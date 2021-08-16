CREATE TABLE pages (
    tx_thuecat_flexform text,
);

CREATE TABLE tx_thuecat_import_configuration (
    title varchar(255) DEFAULT '' NOT NULL,
    type varchar(255) DEFAULT '' NOT NULL,
    configuration text,
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
    errors text DEFAULT '' NOT NULL,
);

CREATE TABLE tx_thuecat_organisation (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text DEFAULT '' NOT NULL,
    manages_towns int(11) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_information int(11) unsigned DEFAULT '0' NOT NULL,
    manages_tourist_attraction int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_thuecat_town (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    tourist_information int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text DEFAULT '' NOT NULL,
);

CREATE TABLE tx_thuecat_tourist_information (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    town int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text DEFAULT '' NOT NULL,
);

CREATE TABLE tx_thuecat_tourist_attraction (
    remote_id varchar(255) DEFAULT '' NOT NULL,
    managed_by int(11) unsigned DEFAULT '0' NOT NULL,
    town int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    description text DEFAULT '' NOT NULL,
    opening_hours text DEFAULT '' NOT NULL,
    address text DEFAULT '' NOT NULL,
    media text DEFAULT '' NOT NULL,
    offers text DEFAULT '' NOT NULL,
    slogan text DEFAULT '' NOT NULL,
    start_of_construction text DEFAULT '' NOT NULL,
    sanitation text DEFAULT '' NOT NULL,
    other_service text DEFAULT '' NOT NULL,
    museum_service text DEFAULT '' NOT NULL,
    architectural_style text DEFAULT '' NOT NULL,
    traffic_infrastructure text DEFAULT '' NOT NULL,
);
