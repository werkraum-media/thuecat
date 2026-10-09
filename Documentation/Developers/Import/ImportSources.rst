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
   A sync scope id. The run asks the API which resources in that scope were created or updated,
   optionally only within the last days, and imports those. Suited to keeping a whole scope up to
   date on a schedule.

``containsPlace``
   The id of one place. The run fetches it and imports everything it lists in
   ``schema:containsPlace``. Suited to "everything in this region".

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
   The root URLs of the run. Requests made here go through the same HTTP client, with the same
   timeouts and retries, as the rest of the run.

Registration is automatic through the ``import.url.provider`` tag the interface carries.

The configuration record needs to offer the type as well:

#. An item in the ``type`` select of :sql:`tx_thuecat_import_configuration`.
#. The type's fields in
   :php:`WerkraumMedia\ThueCat\Typo3\FlexForm\ImportConfigurationDataStructure`. Fields every type
   has — storage page, file folder, API key, tuning values — are shared there and need not be
   repeated.
#. Labels in :file:`locallang_flexform.xlf`, including the sheet title
   ``importConfiguration.<type>.sheetTitle``.
#. A getter on :php:`WerkraumMedia\ThueCat\Domain\Model\Backend\ImportConfiguration` for each new
   field, for the provider to read.

A run whose configuration no provider claims stops with an exception before anything is fetched.