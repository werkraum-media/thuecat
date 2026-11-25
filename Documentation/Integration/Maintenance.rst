.. include:: ../Includes.txt
.. _maintenance:

===========
Maintenance
===========

.. contents:: On this page
   :local:
   :depth: 1

.. _maintenance-upgrade-jobs:

Upgrading to import jobs
========================

Imports run as :ref:`jobs <import-jobs>` from this version on. The command
``thuecat:importviaconfiguration`` and its scheduler tasks are gone; each import configuration
carries its own schedule. The steps below move an installation of thuecat 7 over.

.. _maintenance-upgrade-jobs-before:

Before the update
-----------------

#. Record the scheduler tasks that run ``thuecat:importviaconfiguration``: the configuration each
   one names, its interval or cron expression, whether it is enabled, and its options. The
   migration deletes the tasks and cannot be undone.

#. Make sure the database port is an integer in :file:`config/system/settings.php` or
   :file:`config/system/additional.php`, for example ``'port' => 3306``, not ``'3306'``. The
   ``doctrine`` transport of the message bus passes the port on unconverted, and a string port
   makes every queued message fail.

.. _maintenance-upgrade-jobs-update:

The update
----------

#. Update the package. :composer:`typo3/cms-scheduler` is now required and comes with it.

#. Update the database schema:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the upgrade wizards one by one, in this order. A wizard that is not due yet when it runs is
   marked as done and not offered again, so do not leave the order to ``upgrade:run`` without an
   argument:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run schedulerDatabaseStorageMigration
      vendor/bin/typo3 upgrade:run thuecat_importConfigurationFlexFormMigration
      vendor/bin/typo3 upgrade:run thuecat_importSchedulerTaskMigration

   #. Core's migration of the scheduler tasks gives the import tasks the task type the third wizard
      looks for.

   #. :guilabel:`ThueCat: Move import configuration settings out of the FlexForm`
      (``thuecat_importConfigurationFlexFormMigration``) writes the settings of every import
      configuration to the fields that replace the FlexForm, and its URLs to URL records. It
      reports:

      *  a setting it does not know; that configuration is left as it is
      *  a run budget above 300 seconds, which is not carried over as message budget
      *  an API domain other than ``https://cdb.thuecat.org``, which is dropped

   #. :guilabel:`ThueCat: Move import scheduler tasks onto their configurations`
      (``thuecat_importSchedulerTaskMigration``) writes the interval, the next execution and the
      media option of each task to the configuration it names, and deletes the task. Of several
      enabled tasks for one configuration, the one with the shortest interval wins. It reports the
      option ``--fresh``, cron expressions and configurations run by several tasks. It also fills
      :guilabel:`Last run` and :guilabel:`Last successful run` from the import logs, so a sync
      channel goes on with its window.

   Compare the reports with what was recorded before the update, and adjust the schedules in the
   import configurations.

#. Set up the message worker and the supervising task, see :ref:`import-jobs-requirements`.

.. _maintenance-upgrade-jobs-changes:

What changed
------------

*  The command ``thuecat:importviaconfiguration`` is removed. Imports are started in the module
   :guilabel:`ThüCAT > Imports` or by the schedule of their configuration. The options
   ``--fresh`` and ``--no_media`` became :guilabel:`Bypass cache` and :guilabel:`Skip media` of a
   manual start; :guilabel:`Skip media` is also a field of the configuration.
*  The API domain setting is removed. Resources come from ``https://thuecat.org``, media keys go to
   ``https://cdb.thuecat.org``, see :ref:`integration-configuration-import-configuration-common`.
*  The Extension Configuration ``runBudget`` is replaced by ``messageBudget``, which bounds one item
   of a job instead of a whole run, see :ref:`extension-configuration-import-tuning-values`. A
   value set for ``runBudget`` is not carried over.
*  The column :sql:`configuration` of :sql:`tx_thuecat_import_configuration`, which held the
   FlexForm, stays until the next major version, so settings the wizard did not migrate are kept.
   To migrate a configuration the wizard reported, remove the unknown setting from its
   :sql:`configuration` value in the database, then mark the wizard as not done and run it again:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:mark:undone thuecat_importConfigurationFlexFormMigration
      vendor/bin/typo3 upgrade:run thuecat_importConfigurationFlexFormMigration

.. _maintenance-cleanup-tasks:

Recommended cleanup tasks
=========================

The extension creates no scheduler tasks. The two tasks that process imports are described in
:ref:`import-jobs-requirements`. An instance should set up the following ones as well in
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

   *  -  :guilabel:`Table garbage collection`
      -  Table ``tx_thuecat_import_job``, the import jobs; 180 days unless set otherwise
      -  weekly

   *  -  :guilabel:`Table garbage collection`
      -  Table ``tx_thuecat_import_job_item``, the items of the import jobs; 180 days unless set
         otherwise
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
      -  the message worker imports and removals need, see :ref:`removal-module-worker`
      -  every minute

   *  -  :guilabel:`thuecat:import:supervise`
      -  none; starts due imports and resumes broken ones, see :ref:`import-jobs-requirements`
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
