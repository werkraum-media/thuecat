.. include:: ../../Includes.txt
.. _import-tuning:

=============
Import tuning
=============

Five settings bound what an import job may do. All five are set installation-wide in the Extension
Configuration, :guilabel:`Admin Tools > Settings > Extension Configuration >
thuecat`. The fields are pre-filled with the shipped defaults, so the values in effect are visible.

Two of them, ``messageBudget`` and ``fetchCacheLifetime``, can also be set per import configuration
record (see :ref:`integration-configuration-import-configuration-common`). They are judgements about
one import: how long one of its items may take and how fresh its data has to be. The HTTP settings —
``readTimeout``, ``connectTimeout`` and ``maxAttempts`` — are Extension Configuration only; they are
a property of the installation's network, not of an import.

None of them is a site setting: they bound a job, and a job belongs to an import configuration, not
to a site.

The precedence for each setting is:

#. the import configuration record, where the setting exists there and is set;
#. the Extension Configuration value, when set;
#. the shipped default.

``0`` and empty both count as "not set" at every level, so a field cleared in the backend returns to
the fallback rather than meaning "unlimited".

The values are described in detail at :ref:`extension-configuration-import-tuning-values`.

.. note::

   The defaults are deliberately generous rather than tuned. The ``messageBudget`` bounds one root
   with everything it references, not a whole configuration, so it does not grow with the size of a
   configuration. It is a backstop against a hung item, not a performance target: an item that
   exceeds it is retried, and dropped after three attempts. A root with very many images on a slow
   upstream may need more.

.. _recovered-retries:

Recovered retries
=================

A request that failed and then succeeded on a later attempt costs time but loses nothing, so it is
easy to miss: the job completes, imports everything and reports success. A job against a struggling
upstream then looks exactly like a healthy one.

Each item that had any such request therefore writes a single ``retriesRecovered`` entry to the
job's import log, stating how many requests recovered and how many extra attempts they cost. It is a
``notice`` — below ``warning`` — so it never changes whether the job is considered successful.

An item in which every request succeeded first time writes no entry, so the presence of one is the
signal. Repeated appearances, or a rising attempt count, indicate the API is degrading.

.. _fetch-cache:

Fetch cache
===========

Fetched API responses are cached in the database (:sql:`cache_thuecat_fetchdata`) for
``fetchCacheLifetime`` seconds, so an item that is retried, or a job started again, does not
re-fetch everything.

To import fresh data, start a job with :guilabel:`Bypass cache`, see :ref:`import-module-start`.
Scheduled jobs never bypass the cache.

A bypassing job still *writes* what it fetches, so later jobs benefit from it; only reading is
skipped. Bypassing changes where responses come from, never what is imported.

An installation that configures this cache itself keeps its own backend and lifetime — the defaults
apply only where nothing is configured:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['thuecat_fetchdata']