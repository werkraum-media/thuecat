.. include:: ../../Includes.txt
.. _frontend-output-routing:

=======
Routing
=======

EXT:thuecat ships route enhancers for its plugins, so detail pages, list pagination, list filters
and the search get readable URLs:

.. code-block:: text

   /ausflugsziel/stadtmuseum-erfurt
   /liste/ort/erfurt+weimar/kategorie/museum/haustiere-erlaubt/2
   /liste?q=Gemeindebrunnen%20Weimar

None of these URLs carries a ``tx_thuecat_…`` parameter or a ``cHash``.

.. contents:: On this page
   :local:
   :depth: 1

.. _frontend-output-routing-route-enhancers:

Route enhancers
===============

.. include:: _Snippets/RouteImport.rst.txt

An enhancer only applies to pages with the matching page role, see
:ref:`frontend-output-routing-page-role`. Pages without the role keep query-string URLs, and those
keep working.

The ``TouristAttractionSearch`` plugin needs no route: its form submits into the list plugin, and
the list redirects to a routed URL.

.. _frontend-output-routing-page-role:

Page role
=========

Each page carrying a routed plugin needs the :guilabel:`ThüCAT page role` in its page properties,
next to :guilabel:`Contains plugin`:

.. list-table::
   :header-rows: 1

   * - Role
     - Value
     - Set it on
   * - Tourist attraction detail
     - ``thuecat_touristattractionshow``
     - the attraction detail page
   * - Trail detail
     - ``thuecat_trailshow``
     - the trail detail page
   * - Tourist attraction list
     - ``thuecat_touristattractionlist``
     - every page with a list or filtered list, including the search result page

The role is the same in all languages of a page; translations take it from the default language.

.. _frontend-output-routing-what-editors-see:

What editors see
----------------

On a page with routed ThüCAT plugins the page module shows the page's current role. When the role
does not match the plugins on the page, or is empty, it says which role to set. Pages without
routed plugins show nothing.

EXT:thuecat registers no content elements, so it cannot tell which plugin a content element
renders. The package that defines the content elements tells it:

.. include:: _Snippets/PageRoleMapping.rst.txt

.. _frontend-output-routing-detail-pages:

Detail pages
============

A detail URL is the detail page's path followed by the record's slug. Records get their slug from
their title when they are created; existing records get one from the upgrade wizard, see
:ref:`maintenance-slugs`. A slug does not change when the title changes upstream, so URLs stay
stable.

Slugs are unique within a site and resolve only on the site whose page tree stores the record. A
slug of another site gives 404.

A translated record has a slug of its own and resolves under its language's base:
``/en/attraction/city-museum-erfurt``. The translated slug under another language's base, for
example without ``/en/``, gives 404.

.. _frontend-output-routing-lists:

Lists
=====

Pagination
   The page number is the last path segment: ``/liste/2``. The first page has none.

Filters
   Each active filter is a segment in a fixed order: towns, categories, keywords, then the flags.
   Relation filters carry a label and the slugs of the selected records, joined by ``+`` and
   sorted, so a selection has one URL whatever order it was picked in:

   .. list-table::
      :header-rows: 1

      * - Filter
        - German
        - English
      * - Towns
        - ``/ort/erfurt+weimar``
        - ``/town/erfurt+weimar``
      * - Categories
        - ``/kategorie/museum``
        - ``/category/museum``
      * - Keywords
        - ``/stichwort/romantisch``
        - ``/keyword/romantic``
      * - Pets allowed
        - ``/haustiere-erlaubt``
        - ``/pets-allowed``
      * - Free entry
        - ``/eintritt-frei``
        - ``/free-entry``
      * - Public access
        - ``/oeffentlich-zugaenglich``
        - ``/public-access``

   Towns and categories get their slugs like detail records. A segment with a slug no record of the
   site carries gives 404. The labels follow the language's locale: English for ``en_*``, German
   otherwise.

   Filters the content element locks in its settings are not part of the URL; they apply anyway.

Search
   The search word is the query argument ``q``, next to the path: ``/liste/ort/erfurt?q=Dom``. The
   search form submits into the list, the list redirects there, and pagination keeps it.

   ``q`` is excluded from the cHash. The list and search actions are not cached in the page cache,
   so no cached page depends on it.

To change labels or segment values, copy
:file:`EXT:thuecat/Configuration/Routes/TouristAttractionList.yaml` into your site configuration
instead of importing it. The ``requirements`` of each flag must list every value its ``localeMap``
maps.

A complete example for the attraction detail page is shown in :ref:`routing-example`.
