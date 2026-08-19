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

.. _import-category-based-anchors:

Category based anchors
----------------------

Several imported properties are stored as :sql:`sys_category` records — the categories
derived from an object's type, the :ref:`keywords <import-keywords>` it carries, and
further filter properties as they are added. Each such property is anchored by a pair
of settings: the folder its categories are stored in, and the parent category they are
created beneath.

Anchors are configured **per site and per import target**, not per import configuration.
That is deliberate. They are structural data every imported object uses the same way, and
a filter in the frontend has to be able to identify the parent category its selection
rests on. With the anchors defined once per site, a plugin can.

The import target comes from the import configuration's :guilabel:`Import target` field
and says which kind of records a run writes — ``thuecat`` objects or ``events``. One site
can hold an import configuration of each, so every anchor setting names its target: an
import reads only its own, and the two category trees stay apart. A configuration that
carries no target counts as ``thuecat``; one carrying a value that matches no known target
is rejected before the run fetches anything.

Every anchored property carries its own pair per target, resolved independently of the
others. Properties may point at the same storage folder, but each pair stays separately
configurable, because imported categories can legitimately live in different folders. The
settings are grouped under :guilabel:`Import` in the site settings editor, one labelled
pair per property and target.

Each anchor is resolved from the first level that supplies a positive page or category
uid:

Site settings
   Of the site owning the import configuration's storage page. Set them in
   :guilabel:`Site Management > Sites`. A site offers them once it lists the
   ``werkraummedia/thuecat-import`` set among its own dependencies — importing is a
   decision of the site, so no other set pulls it in.

Extension Configuration
   Installation-wide, via :guilabel:`Admin Tools > Settings > Extension Configuration >
   thuecat`. Used only when the site supplies nothing — it is the fallback for
   installations that declare no site set. One value per target applies to every site, so
   it fits single-site installations and installations where all imports of that target
   share one category tree.

Unset
   ``0``, or nothing configured anywhere. That kind's mapping is switched off and the
   import runs without it. Reaching the end of the chain is the only way an anchor becomes
   unset — no error condition is ever downgraded to it.

Per kind, both anchors must be set or neither. Setting only one is rejected before the
import fetches anything, as is an anchor outside the site that owns the storage page. The
two kinds are validated independently: a broken keyword pair says nothing about the
category pair. Failure messages name the settings to correct, including their target.

The values actually in effect are reported at the start of every run — see
:ref:`effective-settings`.

.. _import-keywords:

Keywords
--------

ThueCat objects carry ``schema:keywords``: a controlled vocabulary of terms grouped
into sets, alongside keywords editors typed by hand. The import stores them as
:sql:`sys_category` records, so they can be selected in the backend and used to
filter records in the frontend.

Keywords are a **separate property from the categories** derived from an object's
type. They never share a tree, a record or an identifier, even where a keyword and a
category happen to carry the same title.

Keyword import is switched on by the keyword pair of site settings — a storage folder
and a parent category. See :ref:`import-category-based-anchors`.

Both must be set, or neither. Leaving both empty switches keyword import off; setting
only one is rejected before the import fetches anything. The anchor must lie inside
the same site as the record storage page.

Because the anchors belong to the site, every import writing into that site shares one
keyword tree. Places and events imported into the same site therefore share it too.

The upstream group structure is mirrored as intermediate categories, so a term appears
beneath a category representing its set rather than in one flat list. Only the keyword
a record actually carries becomes a relation; the groups above it exist to organise the
tree.

Editors may rename an imported keyword category. Renames survive re-import: matching is
on the stored remote identifier, never on the title. Titles are taken from upstream only
when the category is first created.

Keywords no longer supplied by upstream lose their relation on the next import, while
the category record itself remains for editors to keep using. A keyword that could not
be fetched is left alone rather than removed, since a failed request cannot be
distinguished from an upstream deletion.

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

.. _effective-settings:

Effective settings of a run
---------------------------

The values driving an import come from three places — the import configuration record,
the site settings and the Extension Configuration — so each run reports what it actually
used before it fetches anything.

The report is written to the import log as its first entry, at severity ``debug``, and
shown in the :guilabel:`Summary` column of the backend module. Command line runs print
it as well, at normal verbosity; :bash:`--quiet` suppresses the console output while the
log entry is still written.

It covers the storage page, the file folder, the API domain, the import target, that
target's four :ref:`category anchors <import-category-based-anchors>` and the five
:ref:`tuning settings <import-tuning>`. Only the run's own target appears; listing the
other one's anchors would suggest they had a say in the run. An anchor nothing supplies is
reported as ``unset`` rather than ``0``, so a switched-off mapping is visible as a decision
rather than a number.

The API key is never part of the report — not its value, not a masked rendering, not its
length.

.. note::

   This is the quickest way to answer "why did this run behave like that": the reported
   values are the resolved ones, so a setting that never took effect is visible without
   tracing the fallback chain by hand. A category kind reported as ``unset`` after the
   site settings were filled in usually means the value was written for a different site
   than the one owning the storage page — or under a different import target than the one
   the report names.

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

.. _import-without-media:

Import without media
--------------------

Media dominates the cost of a run: every image is an API request for its
metadata plus a download of the file, while the records carrying them are
comparatively cheap. To import records without media:

.. code-block:: bash

   vendor/bin/typo3 thuecat:importviaconfiguration <uid> --no_media

No media is fetched, downloaded or related, in either shape the API uses —
images referenced by URL, which each cost a request, and images inlined into the
record's own response. The file folder is not touched either: neither the
write-access probe nor the per-run staging folder is created, so the run
succeeds even where the configured folder is missing or read-only.

Useful for restoring records after an aborted run, filling a fresh installation,
or reproducing an import problem that has nothing to do with images.

.. note::

   Media already imported is not removed. The option controls what *this* run
   imports, not what earlier runs stored, so records keep the file references
   they already have. Running again without :bash:`--no_media` imports the media
   that was skipped.
