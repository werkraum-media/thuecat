.. include:: Includes.txt
.. _configuration:

=============
Configuration
=============

.. _api-key:

API Key
-------

Some API requests are only possible by providing an API Key.
This key can be configured via "Extension Configuration".

.. _storage-pids:

Storage Pids
------------

The configuration of imports is stored within TYPO3 records.
Those records can be created via backend module.
Each new record is stored on a default page. The default is ``0``.
That would prevent editors from creating and editing records.
This page uid can be configured via TypoScript::

   module {
       tx_thuecat {
           settings {
               newRecordPid {
                   tx_thuecat_import_configuration = 10
               }
           }
       }
   }

.. _import-configuration:

Import configuration
--------------------

Each import is defined via a special import configuration record.
This record can be created via TYPO3 backend module.

There are different configurations available:

Static list of URLs
   Allows to define a list of URLs which should be imported.
   These URLs should reference a single resource to import without any given parameters like a format.

Synchronization area
   Allows to import a so called "Synchronisationsbereich".
   Find out more at https://cms.thuecat.org/developer.
   Add the given ``syncScopeId`` to the configuration to update the given resources
   for that specific sync scope.
   This requires an configured :ref:`api-key`.

All configurations also provide an input to define the page where records should be
stored and updated. This page uid is also used to fetch accordingly site
configuration. The related languages are used during the import.

.. _import-tuning:

Import tuning
-------------

Five settings bound what an import run may do. Each is available in two places:

Extension Configuration
   Installation-wide, via :guilabel:`Admin Tools > Settings > Extension Configuration > thuecat`.
   The fields are pre-filled with the shipped defaults so the values in effect are visible.

Import configuration record
   Per configuration, in the backend module. An empty field means "not set" and
   falls back to the installation-wide value.

Deliberately **not** site configuration: these bound a run, and a run belongs to
an import configuration, not to a site.

The precedence for each setting is:

#. the import configuration record, when set;
#. the Extension Configuration value, when set;
#. the shipped default.

``0`` and empty both count as "not set" at every level, so a field cleared in the
backend returns to the fallback rather than meaning "unlimited".

.. list-table::
   :header-rows: 1

   * - Setting
     - Default
     - Meaning
   * - ``readTimeout``
     - 120 s
     - How long a single request may take to deliver its response. Without it one
       unresponsive host blocks the run indefinitely.
   * - ``connectTimeout``
     - 30 s
     - How long establishing the connection may take.
   * - ``maxAttempts``
     - 3
     - How often a failing request is tried. Retries apply to transport failures and
       5xx responses only — a 4xx is never retried. An exhausted retry is recorded in
       the import log naming the URL, the cause and the attempt count. Requests that
       recovered are summarised too; see :ref:`recovered-retries`.
   * - ``runBudget``
     - 86400 s
     - How long a whole run may take. When exceeded the run aborts deliberately at the
       next phase boundary, writes its import log and exits non-zero, rather than being
       killed with nothing on disk. A pass already under way is never interrupted.
   * - ``fetchCacheLifetime``
     - 900 s
     - How long a fetched API response stays reusable. See :ref:`fetch-cache`.

.. note::

   The defaults are deliberately generous rather than tuned, and the ``runBudget``
   most of all. Upstream latency is high enough that a strict budget would abort
   healthy runs, so the budget is a backstop against a hung run, not a performance
   target. Installations with large configurations should expect to raise it rather
   than assume the default fits.

.. _recovered-retries:

Recovered retries
-----------------

A request that failed and then succeeded on a later attempt costs time but loses
nothing, so it is easy to miss: the run completes, imports everything and reports
success. A run against a struggling upstream then looks exactly like a healthy one.

Each run that had any such request therefore writes a single ``retriesRecovered``
entry to its import log, stating how many requests recovered and how many extra
attempts they cost. It is a ``notice`` — below ``warning`` — so it never changes
whether the run is considered successful, and the command still exits 0.

A run in which every request succeeded first time writes no entry, so the presence
of one is the signal. Repeated appearances, or a rising attempt count, indicate the
API is degrading before it starts failing outright.

.. _fetch-cache:

Fetch cache
-----------

Fetched API responses are cached in the database (:sql:`cache_thuecat_fetchdata`)
for ``fetchCacheLifetime`` seconds, so a run that aborts part-way does not re-fetch
everything when it is started again.

To run against fresh data, bypass the cache:

.. code-block:: bash

   vendor/bin/typo3 thuecat:importviaconfiguration <uid> --fresh

A bypassing run still *writes* what it fetches, so later runs benefit from it; only
reading is skipped. Bypassing changes where responses come from, never what is
imported.

An installation that configures this cache itself keeps its own backend and
lifetime — the defaults apply only where nothing is configured:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['thuecat_fetchdata']
