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
:ref:`import-configuration` needs to be available in the system. They are provided as records and
can be executed via a CLI command or as a scheduled task.

.. _cli-command:

CLI Command
===========

The extension provides a command that imports data from available import configurations.
For inspection, call

.. code-block:: shell

    vendor/bin/typo3 thuecat:importviaconfiguration --help

The <configuration> parameter is mandatory and describes the uid of the import configuration record
that should be executed.

Available options are:

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - :shell:`--fresh`
     - Ignores cached API responses and fetches every resource from the API. Fetched responses are
       still written to the cache. See :ref:`fetch-cache`.
   * - :shell:`--no_media`
     - Imports without media: no media resource is fetched, downloaded or related. See
       :ref:`import-without-media`.
   * - :shell:`--quiet`, :shell:`-q`
     - Suppresses all console output, including the effective settings. The import log is still
       written.
   * - :shell:`-vvv`
     - Prints every fetched and resolved item with the time since the previous one. Only when a
       terminal is attached.

Without :shell:`-vvv` the command prints the effective settings and a headline per phase; with a
terminal attached it adds a progress counter for phases with a known position. :shell:`-v` and
:shell:`-vv` add nothing.

How a run reports its outcome to a scheduler or CI job is described in :ref:`cli-exit-codes`. The
result is available in the BE-Module :guilabel:`ThueCat - Imports`. For a detailed inspection,
consult the Database tables :sql:`tx_thuecat_import_log` for the overview and
:sql:`tx_thuecat_import_log_entry` for each single entry.

.. _effective-settings:

Effective settings of a run
===========================

The values driving an import come from three places — the import configuration record, the site
settings and the Extension Configuration — so each run reports what it actually used before it
fetches anything.

The report is written to the import log as its first entry, at severity ``debug``, and shown in the
:guilabel:`Summary` column of the backend module. Command line runs print it as well, at normal
verbosity; :bash:`--quiet` suppresses the console output while the log entry is still written.

It covers the storage page, the file folder, the API domain, the four
:ref:`category anchors <configuration-category-anchors>` of every scope and the five
:ref:`tuning settings <import-tuning>`. Each scope is reported with what it supplies itself; the
fallback to ``thuecat`` is decided per record and does not show here. An anchor nothing supplies is
reported as ``unset`` rather than ``0``, so a switched-off mapping is visible as a decision rather
than a number.

The API key is never part of the report — not its value, not a masked rendering, not its length.

.. note::

   This is the quickest way to answer "why did this run behave like that": the reported values are
   the resolved ones, so a setting that never took effect is visible without tracing the fallback
   chain by hand. A category kind reported as ``unset`` after the site settings were filled in
   usually means the value was written for a different site than the one owning the storage page, or
   under a different scope's name.



.. _import-without-media:

Import without media
====================

Media dominates the cost of a run: every image is an API request for its metadata plus a download of
the file, while the records carrying them are comparatively cheap. To import records without media:

.. code-block:: bash

   vendor/bin/typo3 thuecat:importviaconfiguration <uid> --no_media

No media is fetched, downloaded or related, in either shape the API uses — images referenced by URL,
which each cost a request, and images inlined into the record's own response. The file folder is not
touched either: neither the write-access probe nor the per-run staging folder is created, so the run
succeeds even where the configured folder is missing or read-only.

Useful for restoring records after an aborted run, filling a fresh installation, or reproducing an
import problem that has nothing to do with images.

.. note::

   Media already imported is not removed. The option controls what *this* run imports, not what
   earlier runs stored, so records keep the file references they already have. Running again without
   :bash:`--no_media` imports the media that was skipped.

.. _cli-exit-codes:

Exit codes
==========

The command's exit code follows the highest severity the run wrote to its import log:

.. list-table::
   :header-rows: 1

   * - Exit code
     - Meaning
   * - ``0``
     - The run completed. The closing message says whether it completed cleanly or with warnings;
       records were imported either way, and the import log names the cause of a warning.
   * - ``1``
     - The run logged an ``error`` or worse. This includes a run that exceeded its ``runBudget`` and
       aborted. The import log holds the details.
   * - non-zero
     - The command stopped with an exception: the configuration uid is not numeric or does not
       exist, or the configuration failed validation before anything was fetched, for example a
       category anchor pair set only halfway. A run that fails after it started still writes its
       import log before the exception is reported.

Warnings deliberately do not fail the command, so a scheduler does not flag a run as broken that
imported its records.
