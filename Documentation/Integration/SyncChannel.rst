.. include:: ../Includes.txt
.. _sync-channel:

====================================
Importing events from a sync channel
====================================

The import configuration type :guilabel:`Sync API channel (events)` (``syncChannel``) imports the
events of one channel of the ThueCat sync API on ``https://widgets.thuecat.org``. It is the only type
that imports licence-restricted ("private") event images: the sync API serves each event as one
merged record that carries the private image in place of the public one.

The type imports events only. Places, organisations and trails keep coming from the other
:ref:`import-configuration` types.

.. contents:: On this page
   :local:
   :depth: 1

.. _sync-channel-fields:

Fields
======

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``storagePid`` (required)
     - Target folder for the imported events.
   * - ``fileFolder`` (required)
     - Target folder for images and other media files.
   * - ``syncChannelId`` (required)
     - ID of the channel, as ThueCat issued it.
   * - ``apiKey`` (required)
     - The key ThueCat issued for this channel.
   * - ``runBudget``, ``fetchCacheLifetime``
     - Optional. Override the Extension Configuration values, see :ref:`import-tuning`.

The type offers no ``apiDomain``: the host is always ``https://widgets.thuecat.org``. An API domain
stored while the record had another type is ignored. Neither does it offer ``fetchLastXDays``; the
window of changes is derived, see :ref:`sync-channel-window`.

The key is presented in the form each host expects:

* The sync API receives it as an ``Authorization`` header.
* Media downloads from ``cdb.thuecat.org`` receive it as ``api_key`` query parameter.
* No other host receives it. ``https://thuecat.org/resources/`` needs no key.

Imported events keep ``https://thuecat.org`` as their ``source_url``, as events imported by the other
types do.

.. _sync-channel-window:

The window of changes
=====================

Each run asks the channel for the events created, updated, removed or expired since a point in time.
That point is computed when the run starts:

* **The storage folder holds no event:** five years before the run. Deleted events and translations
  do not count.
* **Otherwise:** one day before the creation time of the most recent successful run of this
  configuration. The day of overlap absorbs clock skew and upstream changes indexed late;
  re-importing an unchanged event changes nothing.
* **No successful run is logged:** five years as well.

A run is successful unless its import log holds an entry of type ``runAborted``, ``runFailed`` or
``fetchingError``. Skipped references, mapping errors, DataHandler errors and the
:ref:`empty-folder warning <sync-channel-empty-folder>` do not count against it: they concern single
records, and letting them reset the window would fetch the whole history again for one bad event.
A run that is not successful leaves the window where it was, so the next run asks for the same
changes again.

Every event the change list names is imported, unless the channel also lists it as removed or
expired. Each event is fetched on its own. An event the channel lists but answers with an empty
response is logged as ``fetchingError``, and the run goes on with the next one.

If the change list or the expiry list cannot be read, the run fails before it fetches any event, and
nothing is deleted.

.. _sync-channel-removal:

Removal applies to the whole site
=================================

After the import, every event the channel lists as removed or expired is deleted, in the whole site
the storage folder belongs to. It does not matter which import configuration imported the event or
which folder of the site holds it. Events of other sites are not touched.

This follows from record identity: a site holds one event per ThueCat ID, whichever configuration
wrote it, see :ref:`developers-import-records`. Deleting only this configuration's events would leave
an event the channel declares invalid in place.

Events are deleted through the DataHandler, so their translations, dates and file references go with
them. A deletion that fails is logged as ``dataHandlerError``; the event stays listed and is deleted
by a later run, as long as the channel still reports it within the window.

Nothing is deleted when the run was aborted by its run budget, or when either list could not be read.

.. warning::

   A ``syncChannel`` configuration deletes events that another configuration of the same site
   imported. Keep this in mind before adding one to a site that also imports events by other means.

.. _sync-channel-references:

References are asked of the channel first
=========================================

Events refer to other records by ``https://thuecat.org/resources/<id>`` URLs: media, authors,
keywords. On a ``syncChannel`` run, each such reference is first requested from the sync channel:

* **The sync channel serves the ID:** its record is used, including a private image it carries.
  ``thuecat.org`` is not asked.
* **The sync channel answers with an empty response:** the reference is fetched from the URL it carries,
  as on the other types. This is not logged.

Either way, the related record keeps the URL the data carries as its identity; only the source of its
content changes. References to other URLs, such as vocabulary terms, are fetched from their URL
directly. Each reference is asked of the sync channel once per run.

The channel comes first because a record fetched from ``thuecat.org`` carries only the public image.
Fetching a reference there would import the public image, or replace a private one already stored.

.. _sync-channel-empty-folder:

Empty storage folder warning
============================

If the storage folder holds no event after a run, the import log carries a ``storageFolderEmpty``
entry with severity warning, shown in the backend module :guilabel:`ThueCat - Imports`.

The usual cause: every event of the channel already exists in another folder of the site, so the run
updated those records instead of creating new ones in its own folder. Check whether another import
configuration of the site imports the same events.

The warning does not count against the run's success, see :ref:`sync-channel-window`.