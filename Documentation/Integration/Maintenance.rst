.. include:: ../Includes.txt
.. _maintenance:

===========
Maintenance
===========

.. contents:: On this page
   :local:
   :depth: 1

.. _maintenance-cleanup-tasks:

Recommended cleanup tasks
=========================

The extension creates no scheduler tasks. An instance should set up the following ones in
:guilabel:`Administration > Scheduler`, see :ref:`typo3/cms-scheduler:start`. Each runs from the
scheduler's cron job, see :ref:`typo3/cms-scheduler:cron-job`.

.. list-table::
   :header-rows: 1
   :widths: 30 45 25

   *  -  Task type
      -  Settings
      -  Frequency

   *  -  :guilabel:`cleanup:deletedrecords`
      -  ``--min-age`` ``15``: permanently deletes records soft-deleted more than 15 days ago, for
         example by an import or a :ref:`removal <removal-module>`
      -  daily

   *  -  :guilabel:`Table garbage collection`, see
         :ref:`typo3/cms-scheduler:table-garbage-collection-task`
      -  Table ``tx_thuecat_import_log_entry``, entries older than 10 days
      -  daily

   *  -  :guilabel:`Table garbage collection`
      -  Table ``tx_thuecat_import_log``, entries older than 10 days
      -  daily

   *  -  :guilabel:`Table garbage collection`
      -  Table ``tx_thuecat_removal_job``, the removal jobs; 180 days unless set otherwise
      -  weekly

   *  -  :guilabel:`referenceindex:update`
      -  none
      -  weekly

   *  -  :guilabel:`Fileadmin garbage collection`
      -  Number of days until removing files from the ``_recycler_`` folders
      -  daily

   *  -  :guilabel:`events:removePast`
      -  none; removes past dates of ext:events, events without dates left and their unused files
      -  daily

   *  -  :guilabel:`messenger:consume`
      -  the message worker the removal module needs, see :ref:`removal-module-worker`
      -  every minute

The weekly reference index update keeps the index sound between removals; a removal job still updates it itself when the
last full update is 24 hours old or older. A file the removal module deletes from a storage with a
``_recycler_`` folder is moved there first and removed for good by the fileadmin garbage collection.

.. _maintenance-slugs:

Slugs
=====

Tourist attractions, trails, towns and categories carry a slug: the URL segment of detail pages and
of list filters, see :ref:`frontend-output-routing`. Records created in the backend or by an import
get their slug from their title. A slug never changes afterwards, also not when the title changes
upstream, so URLs stay stable.

Records without a slug, for example those that existed before the slug columns, get one from the
:ref:`upgrade wizard <t3coreapi:upgrade-wizards>` :guilabel:`ThueCat: Fill empty slugs of detail
and filter records`:

#. Update the database schema, so the slug columns exist:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the wizard, in the Install Tool under :guilabel:`Upgrade > Upgrade Wizard`, or on the
   console:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run thuecat_detailRecordSlugs

#. Flush the caches. The wizard writes the slugs directly, without the DataHandler, so no cache is
   flushed per record:

   .. code-block:: bash

      vendor/bin/typo3 cache:flush

The wizard fills every empty slug, per record and language, and leaves existing slugs untouched. It
covers every detail record kind, including those other packages register, see
:ref:`developers-detail-record-kinds`.

The wizard is repeatable: it is offered again whenever a record without a slug exists, for example
after a release that adds a slug column to another table.

.. _maintenance-event-relations:

Events of places
================

Tourist attractions, organisations, towns and tourist information list the events the import
matched to them, see :ref:`developers-import-event-place-matching`. These relations are stored in
MM tables. Relations an older version stored as comma-separated lists are moved there by the
:ref:`upgrade wizard <t3coreapi:upgrade-wizards>` :guilabel:`ThueCat: Move event relations of
places into MM tables`:

#. Update the database schema, so the MM tables exist:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the wizard, in the Install Tool under :guilabel:`Upgrade > Upgrade Wizard`, or on the
   console:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run thuecat_eventRelationsToMm

The wizard keeps the order of each list and covers translations and deleted records too. It
relates the same events as before, so no cache needs flushing.

The wizard is repeatable: it is offered again while a stored list remains, and running it twice
creates no duplicate relation.

.. _maintenance-meeting-venues:

Meeting venues
==============

Conference hotels and meeting and congress locations are tourist attractions of the record type
meeting venue, see :ref:`frontend-output-meeting-venues`. The import writes the type on every run.
Attractions stored before the record type existed get it from the
:ref:`upgrade wizard <t3coreapi:upgrade-wizards>` :guilabel:`ThueCat: Type stored meeting venues`:

#. Update the database schema, so the record type column exists:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the wizard, in the Install Tool under :guilabel:`Upgrade > Upgrade Wizard`, or on the
   console:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run thuecat_meetingVenueType

#. Flush the caches. The wizard writes the type directly, without the DataHandler, so no cache is
   flushed per record:

   .. code-block:: bash

      vendor/bin/typo3 cache:flush

The wizard types every attraction related to the conference hotel or the meeting and congress
category, translations included. Uids, URLs, categories and relations stay as they are. A site
whose import writes no type categories has nothing the wizard can judge by: its venues get their
type from the next import.

The wizard is repeatable: it is offered again while such an attraction is not yet typed.
