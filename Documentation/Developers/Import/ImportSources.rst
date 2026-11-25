.. include:: ../../Includes.txt
.. _developers-import-sources:

==================
What a run imports
==================

An import configuration either list records (list of static URLs) or it says how to find the roots
of a run (sync Scope or Contains Place). The type of the configuration decides how. Everything after
that — fetching, parsing, following references — is the same for every type.

The existing types
==================

``static``
   A hand-maintained list of resource URLs. Each one is a root. Suited to a fixed set of objects,
   such as the attractions of one city.

``syncScope``
   A sync scope id. The job asks the API which resources in that scope were created or updated,
   optionally only within the last days, and imports those. Suited to keeping a whole scope up to
   date on a schedule.

``containsPlace``
   The id of one place. The job fetches it and imports everything it lists in
   ``schema:containsPlace``. Suited to "everything in this region".

``syncChannel``
   A channel of the sync API. The job reads the channel's change and expiry lists and imports each
   listed record from the channel, then deletes the records listed as removed or expired, one item
   per ThueCat ID, from every record table of the site (:php:`WerkraumMedia\ThueCat\Import\RecordTables`). Unlike the
   other types, it also changes how references are fetched and which hosts receive the key. See
   :ref:`sync-channel`.

Each type is implemented by a URL provider, a class implementing
:php:`WerkraumMedia\ThueCat\Import\UrlProvider\UrlProvider`.

Adding a type
=============

A URL provider answers three questions:

:php:`canProvideForConfiguration()`
   Is this configuration mine? Usually a comparison of the configuration's type. The first provider
   answering yes is used.

:php:`createWithConfiguration()`
   Return a provider set up for this configuration. Providers are shared services, so return a
   configured copy rather than changing the shared instance.

:php:`getUrls()`
   The root URLs of the job, read when the job starts; each becomes an item. Requests made here go
   through the same HTTP client, with the same timeouts and retries, as the items.

Registration is automatic through the ``import.url.provider`` tag the interface carries.

The configuration record needs to offer the type as well:

#. An item in the ``type`` select of :sql:`tx_thuecat_import_configuration`.
#. A column per new field in :file:`ext_tables.sql` and the TCA of
   :sql:`tx_thuecat_import_configuration`, and an entry in its ``types`` whose ``showitem`` lists
   them. Fields every type has — storage folder, file folder, API key, schedule, skip media, tuning
   values — are columns already; repeat them in the ``showitem`` the way the other types do.
#. Labels in :file:`locallang_tca.xlf`.
#. A getter on :php:`WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration` for each new
   field, for the provider to read.

A configuration no provider claims stops the job's start with an exception, before anything is fetched.