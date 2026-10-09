.. include:: ../../Includes.txt
.. _import-tuning:

=============
Import tuning
=============

Five settings bound what an import run may do. All five are set installation-wide in the Extension
Configuration, :guilabel:`Admin Tools > Settings > Extension Configuration >
thuecat`. The fields are pre-filled with the shipped defaults, so the values in effect are visible.

Two of them, ``runBudget`` and ``fetchCacheLifetime``, can also be set per import configuration
record (see :ref:`integration-configuration-import-configuration-common`). They are judgements about
one import: how long it may take and how fresh its data has to be. The HTTP settings —
``readTimeout``, ``connectTimeout`` and ``maxAttempts`` — are Extension Configuration only; they are
a property of the installation's network, not of an import.

None of them is a site setting: they bound a run, and a run belongs to an import configuration, not
to a site.

The precedence for each setting is:

#. the import configuration record, where the setting exists there and is set;
#. the Extension Configuration value, when set;
#. the shipped default.

``0`` and empty both count as "not set" at every level, so a field cleared in the backend returns to
the fallback rather than meaning "unlimited".

The values are described in detail at :ref:`extension-configuration-import-tuning-values`.

.. note::

   The defaults are deliberately generous rather than tuned, and the ``runBudget`` most of all.
   Upstream latency is high enough that a strict budget would abort healthy runs, so the budget is a
   backstop against a hung run, not a performance target. Installations with large configurations
   should expect to raise it rather than assume the default fits.

.. _recovered-retries:

Recovered retries
=================

A request that failed and then succeeded on a later attempt costs time but loses nothing, so it is
easy to miss: the run completes, imports everything and reports success. A run against a struggling
upstream then looks exactly like a healthy one.

Each run that had any such request therefore writes a single ``retriesRecovered`` entry to its
import log, stating how many requests recovered and how many extra attempts they cost. It is a
``notice`` — below ``warning`` — so it never changes whether the run is considered successful, and
the command still exits 0.

A run in which every request succeeded first time writes no entry, so the presence of one is the
signal. Repeated appearances, or a rising attempt count, indicate the API is degrading.

.. _fetch-cache:

Fetch cache
===========

Fetched API responses are cached in the database (:sql:`cache_thuecat_fetchdata`) for
``fetchCacheLifetime`` seconds, so a run that aborts part-way does not re-fetch everything when it
is started again.

To run against fresh data, bypass the cache:

.. code-block:: bash

   vendor/bin/typo3 thuecat:importviaconfiguration <uid> --fresh

A bypassing run still *writes* what it fetches, so later runs benefit from it; only reading is
skipped. Bypassing changes where responses come from, never what is imported.

An installation that configures this cache itself keeps its own backend and lifetime — the defaults
apply only where nothing is configured:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['thuecat_fetchdata']