.. include:: ../Includes.txt
.. _configuration:

=============
Configuration
=============

This page covers the configuration of the import. The configuration of the frontend output and of
the plugins is described in :ref:`frontend-output` and :ref:`frontend-output-plugin-settings`.

.. contents:: Table of contents
   :local:

.. _extension-configuration:

Extension Configuration
=======================

The Extension Configuration settings are global and apply to all configured
:ref:`import configurations <import-configuration>`. They act as a fallback and are consulted last,
when the :ref:`site-set-configuration` does not provide the necessary values.

.. note::

    In a multi-site setup, where each site should contain its own set of imported data, these values
    should be provided via site settings. Otherwise they apply equally to all sites and break the
    site restriction rules.

.. _extension-configuration-api-key:

API Key
-------

Some API requests are only processed by the ThueCat service when an API key is provided. It can be
set here as a global setting, or in the :ref:`import-configuration` for a single import. The global
setting is the fallback if nothing else is set.

.. _extension-configuration-import-tuning-values:

Import tuning values
--------------------

.. list-table::
   :header-rows: 1

   * - Setting
     - Default
     - Meaning
   * - ``readTimeout``
     - 120 s
     - How long a single request may take to deliver its response. Without it one unresponsive host
       blocks the run indefinitely.
   * - ``connectTimeout``
     - 30 s
     - How long establishing the connection may take.
   * - ``maxAttempts``
     - 3
     - How often a failing request is tried. Retries apply to transport failures and 5xx responses
       only — a 4xx is never retried. An exhausted retry is recorded in the import log naming the
       URL, the cause and the attempt count. Requests that recovered are summarised too; see
       :ref:`recovered-retries`.
   * - ``runBudget``
     - 86400 s
     - How long a whole run may take. When exceeded the run aborts deliberately at the next phase
       boundary, writes its import log and exits non-zero, rather than being killed with nothing on
       disk. A pass already under way is never interrupted.
   * - ``fetchCacheLifetime``
     - 900 s
     - How long a fetched API response stays reusable. See :ref:`fetch-cache`.

Find more information about tuning options and their consequences at :ref:`import-tuning`.

.. _extension-configuration-import-targets:

Import Targets
--------------

For importing Tourist Attractions:

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``importThuecatCategoryStoragePid``
     - Target Page holding Category records.
   * - ``importThuecatCategoryParent``
     - uid of the :sql:`sys_category` record that serves as parent for all records imported for the
       category field.
   * - ``importThuecatKeywordsStoragePid``
     - Target Page holding Keyword records.
   * - ``importThuecatKeywordsParent``
     - uid of the :sql:`sys_category` record that serves as parent for all records imported for the
       keywords field.

For importing Trails:

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``importTrailsKeywordsStoragePid``
     - Target Page holding Keyword records.
   * - ``importTrailsKeywordsParent``
     - uid of the :sql:`sys_category` record that serves as parent for all records imported for the
       keywords field.

For importing Events:

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``importEventsCategoryStoragePid``
     - Target Page holding Category records.
   * - ``importEventsCategoryParent``
     - uid of the :sql:`sys_category` record that serves as parent for all records imported for the
       category field.
   * - ``importEventsKeywordsStoragePid``
     - Target Page holding Keyword records.
   * - ``importEventsKeywordsParent``
     - uid of the :sql:`sys_category` record that serves as parent for all records imported for the
       keywords field.

What the category and keyword values do is described in :ref:`configuration-category-anchors`.

The ``importThuecat*`` keys also cover all object types not named explicitly (like organisations,
towns or tourist information).

.. _site-set-configuration:

Site Set Configuration
======================

Site Settings allow for each site to declare its own values, so multi-site instances can operate
independently from each other. Values set here override the global values provided by the
:ref:`extension-configuration`.

The per-object-type values allow separate trees for :sql:`sys_category`-based values such as
categories and keywords, but this is not mandatory. If each storagePid and parent value is set to
the same value, the trees collapse and share their values.

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - :yaml:`import.thuecat.category.storagePid`
     - Folder holding the categories a ThueCat object (Tourist Attraction) import creates.
   * - :yaml:`import.thuecat.category.parent`
     - Category (uid) the imported categories for a ThueCat object (Tourist Attraction) are placed
       beneath.
   * - :yaml:`import.thuecat.keywords.storagePid`
     - Folder holding the keywords a ThueCat object (Tourist Attraction) import creates.
   * - :yaml:`import.thuecat.keywords.parent`
     - Category (uid) the imported keywords for a ThueCat object (Tourist Attraction) are placed
       beneath.
   * - :yaml:`import.events.category.storagePid`
     - Folder holding the categories an event import creates.
   * - :yaml:`import.events.category.parent`
     - Category (uid) the imported categories for an event object are placed beneath.
   * - :yaml:`import.events.keywords.storagePid`
     - Folder holding the keywords an event import creates.
   * - :yaml:`import.events.keywords.parent`
     - Category (uid) the imported keywords for an event object are placed beneath.
   * - :yaml:`import.trails.keywords.storagePid`
     - Folder holding the keywords a trail object import creates.
   * - :yaml:`import.trails.keywords.parent`
     - Category (uid) the imported keywords for a trail object are placed beneath.

.. _configuration-category-anchors:

Category and keyword anchors
----------------------------

The :yaml:`*.category.*` and :yaml:`*.keywords.*` settings, and their ``import…`` counterparts in
the Extension Configuration, come in pairs: a storage folder and a parent category. The import
creates the categories, or keywords, of a record beneath the parent category and stores them in the
configured folder. The category and keyword fields in the backend start their tree at the very same
parent category the configuration names, in the records as well as in content elements. This allows
to maintain separate trees with overlapping values, sorted by context (say, tourist attraction
keywords and trail keywords), but they can also collapse into a single tree just by providing the
same parent to both imports.

* Each record kind has its own pair: ``thuecat`` for tourist attractions, ``events`` for events,
  ``trails`` for trails, which carry keywords only. Organisations, towns, tourist information and
  other records reached only as relations use the ``thuecat`` pair.
* Set both values of a pair, or neither. A pair set halfway, or pointing at a page or category
  outside the site, stops every import into that site before anything is fetched.
* A record kind whose pair is empty uses the ``thuecat`` pair instead. If that is empty too, the
  import writes no categories or keywords of that kind, and the backend fields offer the whole
  category tree of the installation.
* Site settings come first. The Extension Configuration applies only where the site sets nothing, so
  it suits single-site installations or sites sharing one tree per record kind.
* Several pairs may point at the same folder and parent category. The trees then merge and share
  their categories.

Every import run reports the values it actually used, see :ref:`effective-settings`.

.. _import-configuration:

Import configuration
====================

Each import is defined via a special import configuration record.
This record can be created in the TYPO3 backend.

There are different configurations available:

Static list of URLs
   Defines a list of URLs to import.
   These URLs should reference a single resource to import without any given parameters like a
   format.

Synchronization area
   Imports a so-called "Synchronisationsbereich". Find out more at
   https://cms.thuecat.org/developer. Add the given ``syncScopeId`` to the configuration to update
   the given resources for that specific sync scope. This requires a configured API key, either via
   Extension Configuration or within the import configuration.

Contains Place
    Expects an ID that identifies a place and will import all objects that declare a relation to it.

.. warning::

    Each import configuration record expects a storagePid page and will test all other values given
    against this folder's site.
    It will accept only values within this site to prevent records from ending up in several sites.

.. _integration-configuration-import-configuration-common:

Common settings
---------------

Each import configuration offers fields for the following configurations:

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``storagePid``
     - Target folder for imported objects. Independent of object type, records are stored here.
   * - ``fileFolder``
     - Target folder for imported images and other media files. Required.
   * - ``apiDomain``
     - Optional. Which URL to query for resources, e.g. ``https://cdb.thuecat.org`` or
       ``https://cdb.int.thuecat.org``. Falls back to ``https://cdb.thuecat.org`` when left empty.
   * - ``apiKey``
     - Optional. Overrides the global :ref:`API key <extension-configuration-api-key>`.
       Leave empty to use the global key.
   * - ``runBudget``
     - Optional. Overrides the Extension Configuration value, see :ref:`import-tuning`.
   * - ``fetchCacheLifetime``
     - Optional. Overrides the Extension Configuration value, see :ref:`import-tuning`.

Specific settings per import configuration type:

Contains Place (containsPlace)
------------------------------

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``containsPlaceId`` (required)
     - ID of a place, not its full URL, e.g. ``043064193523-jcyt``. The place is fetched from
       ``<apiDomain>/resources/<id>``, and every object it lists in ``schema:containsPlace`` is
       imported.

Static List of URLs (static)
----------------------------

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``urls``
     - Repeatable section, one entry per resource to import.
   * - ``url`` (required)
     - Full URL of a ThueCat resource, without any parameters like API key or format.
   * - ``title``
     - Read-only. Filled on save with the name of the fetched resource.

Synchronization area (syncScope)
--------------------------------

.. list-table::
   :header-rows: 1

   * - Setting
     - Meaning
   * - ``syncScopeId`` (required)
     - ID of a sync scope provided by the ThueCat maintainers. Every object of that scope updated
       within ``fetchLastXDays`` is imported.
   * - ``fetchLastXDays``
     - Number of days of changes to fetch. Empty means one day; ``0`` fetches the whole scope
       without a time limit. Set it to cover the interval between runs with a margin, e.g. ``6``
       for a run every five days.



