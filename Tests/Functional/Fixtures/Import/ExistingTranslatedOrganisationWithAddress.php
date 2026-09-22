<?php

declare(strict_types=1);

/**
 * As the untranslated variant, plus an English organisation row. Its address
 * has no translation yet, so the run has to create one.
 *
 * @var array<string, array<int, array<string, mixed>>> $state
 */
$state = require __DIR__ . '/ExistingUntranslatedOrganisationWithAddress.php';

$state['tx_thuecat_organisation'][1] = [
    'uid' => '8',
    'pid' => '10',
    'sys_language_uid' => '1',
    'l10n_parent' => '7',
    'l10n_source' => '7',
    'remote_id' => 'https://thuecat.org/resources/018132452787-ngbe',
    'title' => 'Erfurt Tourism and Marketing Ltd',
];

return $state;
