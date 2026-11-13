<?php

declare(strict_types=1);

defined('TYPO3') || die();

// Upstream publishes as many images as an editor added; a cap would drop the
// surplus silently on import. Unset, DataHandler falls back to 99999.
// @phpstan-ignore offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible (we put up with TCA Array for now)
unset($GLOBALS['TCA']['tx_events_domain_model_event']['columns']['images']['config']['maxitems']);
