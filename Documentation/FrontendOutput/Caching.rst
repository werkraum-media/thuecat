.. _frontend-output-caching:

=======
Caching
=======

Attraction lists are cached so a visitor rarely waits for one to be built twice.
Three caches do the work, all in the ``pages`` cache group.

.. _frontend-output-caching-caches:

The three caches
================

:sql:`tx_thuecat_teaser`
    One rendered list item, keyed by the record, the detail page it links to and
    the language. An attraction shown by several lists — filtered, curated, on
    different pages — is rendered once and reused by all of them.

:sql:`tx_thuecat_list`
    A whole rendered list, keyed by the plugin, the filter selection, the
    pagination page and the language.

:sql:`tx_thuecat_searchmask`
    A rendered filter form, keyed by the plugin, its page, the selection and the
    language. Deliberately not by the pagination page, so paging through results
    reuses one form.

.. _frontend-output-caching-invalidation:

Invalidation
============

Because the caches belong to the ``pages`` group, everything that already clears
page caches clears these too: the :guilabel:`Clear cache` buttons in the backend,
and any editor saving a record. Editors need do nothing differently — a changed
attraction, town or category takes its cached output with it, and the next
visitor sees the new content.

Entries are discarded by the records they show rather than by age, so nothing
goes stale waiting for a lifetime to run out. A filter combination nobody has
requested before is still built from scratch, but only its unseen items cost
anything: the rest come from the teaser cache.

.. _frontend-output-caching-lifetime:

Lifetime
========

The three caches configure a lifetime of one year. This is not a staleness
setting — invalidation by tag is what keeps content current — it exists because
TYPO3's default would otherwise expire entries after an hour.

Override it per cache in :file:`ext_localconf.php` or
:file:`config/system/additional.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']
       ['tx_thuecat_teaser']['options']['defaultLifetime'] = 3600;

Shortening it only costs rebuilds without making anything fresher.

.. note::

   An import currently discards the caches of every record it visits, whether or
   not that record changed, so lists are rebuilt after each import run. A known
   limitation, to be addressed separately; it costs speed, never correctness.
