/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Modal from '@typo3/backend/modal.js';
import Persistent from '@typo3/backend/storage/persistent.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js';

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

const container = document.querySelector('[data-imports-status]');
const refreshInterval = 60000;
let logAfter = Number(container?.dataset.logAfter ?? 0);

const element = (name, attributes = {}, text = '') => {
    const created = document.createElement(name);
    Object.entries(attributes).forEach(([attribute, value]) => created.setAttribute(attribute, value));
    created.textContent = text;
    return created;
};

// The cells keep their element, so nothing around them is redrawn and every fold keeps its state.
const renderStatus = (cell, job) => {
    cell.replaceChildren(document.createTextNode(job.status));
    if (job.stalled) {
        cell.append(' ', element('span', {class: 'badge badge-warning'}, container.dataset.labelStalled));
    }

    if (job.total > 0) {
        const bar = element('div', {
            class: 'progress mt-1',
            role: 'progressbar',
            'aria-valuenow': job.finished,
            'aria-valuemin': 0,
            'aria-valuemax': job.total,
            'aria-label': container.dataset.labelProgress,
        });
        bar.append(element('div', {class: 'progress-bar', style: `width: ${Math.floor(100 * job.finished / job.total)}%`}));
        cell.append(bar, element('small', {}, `${job.finished} / ${job.total}${job.estimate ? `, ${job.estimate}` : ''}`));
    }
};

const newEntriesList = (uid, row) => {
    let logRow = document.querySelector(`tr[data-job-log="${uid}"]`);
    if (logRow === null) {
        logRow = element('tr', {'data-job-log': uid});
        const cell = element('td', {colspan: 8});
        const details = element('details', {'data-fold': `log-${uid}`});
        details.append(element('summary', {}, container.dataset.labelLog));
        cell.append(details);
        logRow.append(cell);
        row.after(logRow);
    }

    let list = logRow.querySelector('ul[data-log-new]');
    if (list === null) {
        const details = logRow.querySelector('details');
        details.append(element('p', {class: 'mb-0'}, container.dataset.labelNewEntries));
        list = element('ul', {'data-log-new': ''});
        details.append(list);
    }

    return list;
};

const renderLogEntries = (uid, row, entries) => {
    if (entries.length === 0) {
        return;
    }

    const list = newEntriesList(uid, row);
    entries.forEach((entry) => {
        const text = entry.remoteId === '' ? entry.message : `${entry.remoteId}: ${entry.message}`;
        list.append(element('li', {'data-severity': entry.severity}, `[${entry.type}] ${text}`));
        logAfter = Math.max(logAfter, entry.uid);
    });
};

const renderJob = (uid, job) => {
    const row = document.querySelector(`tr[data-job="${uid}"]`);
    if (row === null) {
        return;
    }

    const [status, , , started, ended, done, failed, skipped] = row.cells;
    renderStatus(status, job);
    started.textContent = job.startedAt || '–';
    ended.textContent = job.endedAt || '–';
    done.textContent = job.done;
    failed.textContent = job.failed;
    skipped.textContent = job.skipped;
    renderLogEntries(uid, row, job.logEntries);
};

const refresh = async () => {
    const jobs = Array.from(document.querySelectorAll('tr[data-job]'), (row) => row.dataset.job);
    if (jobs.length === 0) {
        return;
    }

    const response = await new AjaxRequest(container.dataset.importsStatus)
        .withQueryArguments({jobs: jobs.join(','), after: logAfter})
        .get();
    const status = await response.resolve();
    Object.entries(status.jobs).forEach(([uid, job]) => renderJob(uid, job));
};

if (container !== null) {
    // A failed refresh, e.g. after the session expired, is simply tried again a minute later.
    window.setInterval(() => refresh().catch(() => {}), refreshInterval);
}
