.. include:: ../Includes.txt
.. _import:

========================
Import Data from ThueCat
========================

.. contents:: Table of contents
   :local:

Objects provided by ThueCat are retrieved from the service and imported into local database tables
for the instance to use. Images and other files are stored in the file folder the import
configuration names and related to their records. In order to do that, at least one
:ref:`import-configuration` needs to be available in the system.

An import runs as a job in the background. A job is started by hand in the backend module
:guilabel:`ThüCAT > Imports`, or by the schedule of its configuration.

.. _import-jobs:

Import jobs
===========

A job imports one import configuration. When it starts, it reads what the configuration names —
the list of URLs, the objects of a place, or the changes of a sync scope or channel — and stores
one item per object to import, the root. Each item is imported on its own, one after the other,
each in a message of its own. A large configuration thus never has to be written in one go.

Every item fetches its root, follows its references and writes the records. What an earlier item
wrote is found again by a later one, so an object shared by two roots, for example a common
organisation, is stored once.

A configuration has at most one open job. A start while a job is queued or running is refused.

.. _import-jobs-requirements:

Required scheduler tasks
------------------------

Two tasks have to run for jobs to be processed. Without them, jobs stay queued.

The message worker
   Jobs are messages on the asynchronous ``doctrine`` transport of the
   :ref:`message bus <t3coreapi:message-bus>`. The ``messenger:consume`` command processes them,
   the same worker the :ref:`removal module <removal-module>` uses. Set it up as described in
   :ref:`removal-module-worker`.

The supervising task
   ``thuecat:import:supervise`` starts the imports that are due, resumes jobs whose chain of
   messages broke, for example because the worker was killed, and removes media staging folders
   that a killed item left behind. In :guilabel:`Administration > Scheduler`, add a task of the
   type :guilabel:`thuecat:import:supervise`:

   *  :guilabel:`Frequency`: every minute (``* * * * *``)
   *  :guilabel:`Allow Parallel Execution`: off

   Make sure cron starts the scheduler every minute, see :ref:`typo3/cms-scheduler:cron-job`.

.. _import-module:

The imports module
==================

The backend module :guilabel:`ThüCAT > Imports` lists the import configurations whose storage
folder the user may read, grouped by the site of the storage folder. Configurations whose folder
belongs to no site are listed last.

Each configuration shows its type, storage folder, schedule, next and last run, and its five most
recent jobs with their status, trigger, the user who started them, start and end, and the number of
items done, failed and skipped. Beneath each job, its import log lists what happened.

A running job shows its progress and an estimate of the remaining time. A job queued, or running
without progress, for more than five minutes is marked :guilabel:`Stalled`; this usually means
that the message worker does not run.

While it is open, the module refreshes the jobs it shows once a minute, without a reload.

.. _import-module-start:

Starting an import
------------------

:guilabel:`Start` opens a dialog with two options for this job only:

:guilabel:`Skip media`
   Imports without media, see :ref:`import-without-media`. Preselected when the configuration skips
   media on its scheduled imports.

:guilabel:`Bypass cache`
   Ignores cached API responses and fetches every resource from the API. Fetched responses are
   still written to the cache. See :ref:`fetch-cache`.

The job is queued and runs in the background. Starting requires access to the module and to the
configuration's storage folder.

.. _import-module-stop:

Stopping an import
------------------

:guilabel:`Stop` ends the open job of a configuration. The item being imported is finished; all
remaining items are skipped, and the job ends as :guilabel:`stopped`.

.. _import-schedule:

Schedules
=========

An import configuration imports on a schedule of its own, in its :guilabel:`Schedule` fields:

:guilabel:`Interval (seconds)`
   Time between two scheduled imports, with presets for hourly, every six hours, daily and weekly.
   ``0`` imports only when started by hand.

:guilabel:`Next run`
   When the next scheduled import starts. Set it to choose the first start. When a job ends, it is
   moved on by whole intervals until it lies in the future.

:guilabel:`Last run`
   When the last job started, whatever its outcome.

:guilabel:`Last successful run`
   When the last job started that ended as done without a ``runFailed`` entry in its log. A sync
   channel reads its changes since this time, see :ref:`sync-channel-window`.

A scheduled job takes its options from the configuration: it skips media when
:guilabel:`Skip media` is set there, and it never bypasses the cache. A scheduled start is refused
like a manual one while the configuration has an open job; the next due time starts it.

.. _import-job-outcome:

Outcome of a job
================

A job ends with one of these statuses:

:guilabel:`done`
   Every item was imported. Warnings in the log, for example a skipped image, do not change this.

:guilabel:`failed`
   An item could not be imported, or the job could not start. A job fails at its start, before any
   item, when the configuration is rejected (for example a category anchor pair set only halfway),
   when its file folder cannot be resolved and media is not skipped, or when a sync channel's change
   or expiry list cannot be read.

:guilabel:`stopped`
   The job was stopped in the module.

An item that fails is tried again a minute later. Each attempt is logged as ``itemFailed``. After
three failed attempts the item is dropped, logged as ``itemDropped``, and the job goes on with the
next one; the next job imports it again. An item that takes longer than its
:ref:`message budget <extension-configuration-import-tuning-values>` is aborted and counts as a
failed attempt.

For a detailed inspection, consult the database tables :sql:`tx_thuecat_import_log` and
:sql:`tx_thuecat_import_log_entry`. The entries are described in :ref:`developers-import-log`.

.. _effective-settings:

Effective settings of a job
===========================

The values driving an import come from three places — the import configuration record, the site
settings and the Extension Configuration — so each job reports what it actually uses before it
fetches anything.

The report is written to the job's import log as its first entry, at severity ``debug``.

It covers the storage page, the file folder, the four
:ref:`category anchors <configuration-category-anchors>` of every scope and the five
:ref:`tuning settings <import-tuning>`. Each scope is reported with what it supplies itself; the
fallback to ``thuecat`` is decided per record and does not show here. An anchor nothing supplies is
reported as ``unset`` rather than ``0``, so a switched-off mapping is visible as a decision rather
than a number.

The API key is never part of the report — not its value, not a masked rendering, not its length.

.. note::

   This is the quickest way to answer "why did this job behave like that": the reported values are
   the resolved ones, so a setting that never took effect is visible without tracing the fallback
   chain by hand. A category kind reported as ``unset`` after the site settings were filled in
   usually means the value was written for a different site than the one owning the storage page, or
   under a different scope's name.

.. _import-reconciliation:

What a re-import removes
========================

A re-import writes what upstream supplies now. On a record imported as a root of the job, a value
or relation upstream no longer supplies is removed, and each removal is logged as ``removedUpstream`` at severity ``info``,
naming the record, the field and the removed value. A relation list emptied entirely is logged; a
list that only changed is not.

.. _import-reconciliation-kinds:

Covered record kinds
--------------------

Trails, towns, organisations, events, meeting venues and meeting rooms are reconciled when they
are a root, together
with the inline children they own (addresses, opening hours, a trail's way types, conditions and
locations), their categories and keywords, their images, and the fields of their translations.

Tourist attractions, tourist information, parking facilities and the other places are not
reconciled yet, and neither is a record the job reaches only through a reference: on these, a value
upstream no longer supplies stays in the database.

.. _import-reconciliation-removed:

What is removed
---------------

*  A field upstream no longer supplies returns to its default: empty text, ``0`` or no relation.
*  An inline child upstream no longer supplies is deleted, for example an address or an opening
   hours entry.
*  A category or keyword upstream no longer supplies loses its relation. The category itself stays.
*  An image upstream no longer supplies loses its file reference. The file stays in the folder.
*  A field of a translation upstream no longer supplies in that language returns to its default.

.. _import-reconciliation-guards:

What is kept
------------

A failure cannot be told from a removal, so nothing is removed where the import could not ask:

*  A reference that could not be fetched keeps the field it would fill. Only a ``404`` or ``410``
   counts as gone; a server error, a refused or rate-limited request keeps what is stored.
*  An image whose metadata or download failed keeps the images stored in its field.
*  A job that skips media keeps every image.
*  Categories and keywords are kept when the record kind has no
   :ref:`category anchor <configuration-category-anchors>` configured, as the import then does not
   know them.
*  Categories an editor added by hand, without a ``remote_id``, are kept.

.. _import-record-origin:

Origin of an imported record
============================

All import configurations write into the same tables. A record therefore remembers the import
configuration that created it, to tell apart records of, for example, a sync channel and a static
configuration. Three fields hold that origin:

:sql:`origin_configuration_uid`
   The uid of the import configuration.

:sql:`origin_configuration_title`
   Its title at the time of the import. It is a copy, so it survives a renamed or deleted
   configuration.

:sql:`origin_sync_id`
   The sync scope ID of a ``syncScope`` configuration, the sync channel ID of a ``syncChannel``
   configuration, and empty for every other type.

.. _import-record-origin-records:

Records carrying an origin
--------------------------

Every standalone record the import writes carries an origin, whether the configuration names it as
a root or the import reaches it through a reference: rows of :sql:`tx_thuecat_tourist_attraction`,
:sql:`tx_thuecat_trail`, :sql:`tx_thuecat_organisation`, :sql:`tx_thuecat_town`,
:sql:`tx_thuecat_tourist_information`, :sql:`tx_thuecat_place`, :sql:`tx_thuecat_meeting_room`,
:sql:`tx_thuecat_parking_facility`, :sql:`tx_events_domain_model_event`,
:sql:`tx_events_domain_model_organizer` and :sql:`tx_events_domain_model_location`.

Inline children carry none, as they belong to their parent: addresses, opening hours, event dates,
the way types, conditions and locations of trails, and parking facilities stored inside an
attraction. A translation carries the origin of its default-language record.

Records written by the destination.data import of EXT:events carry no origin.

.. _import-record-origin-write-once:

Written once
------------

The import writes the origin only to a record that has none yet, and then writes all three fields
together. A record that has an origin keeps it: a different configuration importing the record
later updates its data, never its origin.

Records that existed before the origin fields were introduced have none. The first import that
touches such a record stamps it with its own configuration, which is not necessarily the one that
created it originally.

Events also keep :guilabel:`Last imported by import configuration`, which every import overwrites.
It names the configuration that imported the event last, while the origin names the one that created
it.

.. _import-record-origin-backend:

In the backend
--------------

The origin fields are shown on the :guilabel:`Import` tab of each record, together with the fields
identifying the record upstream: the remote ID, on events and locations the global ID, and on
events the source name and URL and both import configuration fields. All fields on the tab are
read-only and are not translated.

.. _import-record-origin-frontend:

In the frontend
---------------

Attraction lists can be limited to chosen origins, see :ref:`frontend-output-plugin-settings-origins`.

.. _import-without-media:

Import without media
====================

Media dominates the cost of an import: every image is an API request for its metadata plus a
download of the file, while the records carrying them are comparatively cheap. To import records
without media, choose :guilabel:`Skip media` when starting a job, or set :guilabel:`Skip media` on
the configuration for its scheduled jobs.

No media is fetched, downloaded or related, in either shape the API uses — images referenced by URL,
which each cost a request, and images inlined into the record's own response. The file folder is not
touched either: neither its check at the start nor a staging folder is made, so the job succeeds
even where the configured folder is missing or read-only.

Useful for filling a fresh installation, or reproducing an import problem that has nothing to do
with images.

.. note::

   Media already imported is not removed. The option controls what *this* job imports, not what
   earlier jobs stored, so records keep the file references they already have. A job without
   :guilabel:`Skip media` imports the media that was skipped.
