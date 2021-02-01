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
