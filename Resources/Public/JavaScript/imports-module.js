/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

import Persistent from '@typo3/backend/storage/persistent.js';

const key = 'thuecat_imports.open';

const openFolds = () => Array.from(document.querySelectorAll('details[data-fold][open]'), (details) => details.dataset.fold).join(',');

// Blocks rendered open fire a toggle event on load too; only a change is stored.
let stored = openFolds();

// toggle does not bubble, so it is caught on its way down.
document.addEventListener('toggle', (event) => {
    if (!(event.target instanceof HTMLDetailsElement) || event.target.dataset.fold === undefined) {
        return;
    }

    const current = openFolds();
    if (current !== stored) {
        stored = current;
        Persistent.set(key, current);
    }
}, true);
