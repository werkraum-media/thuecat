/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Modal from '@typo3/backend/modal.js';
import Persistent from '@typo3/backend/storage/persistent.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js';

const container = document.querySelector('[data-imports-status]');
const refreshInterval = 60000;

const element = (name, attributes = {}, text = '') => {
    const created = document.createElement(name);
    Object.entries(attributes).forEach(([attribute, value]) => created.setAttribute(attribute, value));
    created.textContent = text;
    return created;
};

// Only what the user chose differently from a block's default is stored, so a block nobody touched follows its
// default on every render, e.g. the jobs of a configuration whose last job just failed open up.
const folds = {
    open: new Set((container?.dataset.foldsOpen ?? '').split(',').filter((fold) => fold !== '')),
    closed: new Set((container?.dataset.foldsClosed ?? '').split(',').filter((fold) => fold !== '')),
};

// Each store rewrites the whole user settings, so two at once lose one of them: they run one after another, and a
// store still waiting takes the latest choices along.
let saving = Promise.resolve();
let saveQueued = false;
const save = () => {
    if (saveQueued) {
        return;
    }

    saveQueued = true;
    saving = saving
        .then(async () => {
            saveQueued = false;
            await Persistent.set('thuecat_imports.open', Array.from(folds.open).join(','));
            await Persistent.set('thuecat_imports.closed', Array.from(folds.closed).join(','));
        })
        .catch(() => {});
};

const isOpen = (details) => folds.open.has(details.dataset.fold)
    || (details.dataset.foldDefault === 'open' && !folds.closed.has(details.dataset.fold));

// toggle does not bubble, so it is caught on its way down. A block rendered open fires it on insertion too; that
// matches its rendered state and changes nothing.
document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!(details instanceof HTMLDetailsElement) || details.dataset.fold === undefined) {
        return;
    }

    const fold = details.dataset.fold;
    const chosen = details.open !== (details.dataset.foldDefault === 'open');
    const [list, other] = details.open ? ['open', 'closed'] : ['closed', 'open'];
    const changed = (chosen && !folds[list].has(fold)) || folds[other].has(fold) || (!chosen && folds[list].has(fold));
    if (!changed) {
        return;
    }

    folds[other].delete(fold);
    if (chosen) {
        folds[list].add(fold);
    } else {
        folds[list].delete(fold);
    }

    save();
}, true);

// One button per site opens every jobs block of the site, or closes them all once none is closed.
document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-toggle-site-jobs]') : null;
    if (button === null) {
        return;
    }

    const blocks = Array.from(button.closest('section[data-site]').querySelectorAll('details[data-fold^="jobs-"]'));
    const open = blocks.some((details) => !details.open);
    blocks.forEach((details) => {
        details.open = open;
    });
});

// The modal opens in the backend's top window, so the chosen options are carried back into the row's form, which
// is submitted in the module's own frame.
document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-start-import]') : null;
    if (button === null) {
        return;
    }

    const form = button.closest('form');
    Modal.advanced({
        title: button.dataset.title,
        content: form.querySelector('template[data-start-options]').content.cloneNode(true),
        severity: SeverityEnum.info,
        buttons: [
            {
                text: button.dataset.buttonCloseText,
                btnClass: 'btn-default',
                trigger: (_event, modal) => modal.hideModal(),
            },
            {
                text: button.dataset.buttonOkText,
                btnClass: 'btn-primary',
                trigger: (_event, modal) => {
                    modal.querySelectorAll('input[type="checkbox"]:checked').forEach((option) => {
                        form.append(element('input', {type: 'hidden', name: option.name, value: option.value}));
                    });
                    modal.hideModal();
                    form.submit();
                },
            },
        ],
    });
});

// Each configuration's block is rendered by the server as the page renders it, and swapped in whole when it changed.
const rendered = new Map();

const refresh = async () => {
    const blocks = Array.from(document.querySelectorAll('tbody[data-configuration-block]'));
    if (blocks.length === 0) {
        return;
    }

    await saving;
    const response = await new AjaxRequest(container.dataset.importsStatus)
        .withQueryArguments({configurations: blocks.map((block) => block.dataset.configurationBlock).join(',')})
        .get();
    const status = await response.resolve();
    Object.entries(status.configurations).forEach(([uid, html]) => {
        const block = document.querySelector(`tbody[data-configuration-block="${uid}"]`);
        if (block === null || rendered.get(uid) === html) {
            return;
        }

        rendered.set(uid, html);
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        // The page's own record of the user's choices is newer than what the server rendered from.
        template.content.querySelectorAll('details[data-fold]').forEach((details) => {
            details.open = isOpen(details);
        });
        block.replaceWith(template.content);
    });
};

if (container !== null) {
    // A failed refresh, e.g. after the session expired, is simply tried again a minute later.
    window.setInterval(() => refresh().catch(() => {}), refreshInterval);
}
