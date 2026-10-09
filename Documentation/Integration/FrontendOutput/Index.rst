.. include:: ../../Includes.txt
.. toctree::
   :maxdepth: 1
   :titlesonly:

   PluginSettings
   ContentElementExample
   Caching
   DetailPageSections


.. _frontend-output:

===============
Frontend output
===============

EXT:thuecat registers Extbase controller actions and ships template stubs, but **no content
elements**. You define the content elements in your own extension or sitepackage and point them at
the registered plugins, so rendering stays under your control.

Plugins and templates
=====================

The extension name is ``ThueCat``. It registers six plugins, four for tourist attractions and two
for trails. Each plugin renders one template. List plugins render each item through the ``ListItem``
template of their record kind and cache it on its own, so an item shown in several lists is rendered
once. See :ref:`frontend-output-caching`.

Tourist attractions
-------------------

.. list-table::
   :header-rows: 1

   * - Plugin name
     - Purpose
     - Template
     - FlexForm settings
   * - ``TouristAttractionList``
     - Paginated list of attractions, limited to the storage folders in its :sql:`pages` field where
       set, refined by the search-and-filter form on the same page. With an editor preset it becomes
       a filtered list, see `Filtered list`_.
     - ``List``
     - optional preset: ``towns``, ``categories``, ``keywords``, ``petsAllowed``,
       ``isAccessibleForFree``, ``publicAccess``. Optional overrides of the `Site settings`_:
       ``itemsPerPage``, ``page.pid.thuecat_attraction_show``.
   * - ``TouristAttractionListSelected``
     - Fixed set of attractions picked by the editor, in the picked order.
       No filtering, no pagination.
     - ``SelectedList``
     - ``selectedRecords``
   * - ``TouristAttractionSearch``
     - Search-and-filter form. Submits into the list plugin on the same page, or to the configured
       search page.
     - ``SearchForm``
     - none
   * - ``TouristAttractionShow``
     - Detail view of one attraction, taken from the URL or pinned by the editor.
     - ``Show``
     - optional ``selectedRecord``

Trails
------

.. list-table::
   :header-rows: 1

   * - Plugin name
     - Purpose
     - Template
     - FlexForm settings
   * - ``TrailListSelected``
     - Fixed set of trails picked by the editor, in the picked order.
     - ``SelectedList``
     - ``selectedRecords``
   * - ``TrailShow``
     - Detail view of one trail, taken from the URL or pinned by the editor.
     - ``Show``
     - optional ``selectedRecord``

Trails have no filterable list and no search form.

Required pages
==============

The plugins link to each other across pages, but they cannot discover those pages on their own. Set
up the following pages once and feed their uids into the site configuration, see `Site settings`_:

* **Attraction detail page** -- carries the attraction detail content element; target for links to a
  single attraction (``thuecat_attraction_show``).
* **Trail detail page** -- carries the trail detail content element; target for links to a single
  trail (``thuecat_trail_show``).
* **Search result page** -- carries a list content element; search forms on pages without a list
  submit here (``thuecat_attraction_search``). See `Search and list on one page`_.
* **Storage folder** -- where the import configuration writes the records (``storagePid``). Not a
  site setting: a list content element can be narrowed to it through its :sql:`pages` field, and
  left empty it is not limited to any storage page.

.. _frontend-output-site-settings:

Site settings
=============

Provide a site set that maps site settings onto the plugin configuration and include it in your
site. Fill in the page ids under :guilabel:`Settings` in the site configuration.

.. code-block:: yaml
   :caption: EXT:myextensions/Configuration/Sets/<YourSet>/settings.definitions.yaml

   settings:
     thuecat.pois.pid_show:
       label: 'Detail Page for Tourist Attractions'
       description: 'The page providing the detail pages for tourist attractions'
       category: 'page.pids'
       type: 'int'
       default: 0
     thuecat.pois.pid_search:
       label: 'Search Result Page for Tourist Attractions'
       description: 'The page providing the list of tourist attractions, used as target for search form submissions'
       category: 'page.pids'
       type: 'int'
       default: 0
     thuecat.trails.pid_show:
       label: 'Detail Page for Trails'
       description: 'The page providing the detail pages for trails'
       category: 'page.pids'
       type: 'int'
       default: 0
     thuecat.settings.itemsPerPage:
       label: 'Tourist Attractions per Page'
       description: 'Number of tourist attractions shown per page in the list view'
       category: 'list'
       type: 'int'
       default: 20

.. code-block:: typoscript
   :caption: EXT:myextension/Configuration/Sets/MySet/setup.typoscript

   plugin.tx_thuecat.settings {
       page.pid {
           thuecat_attraction_show = {$thuecat.pois.pid_show}
           thuecat_attraction_search = {$thuecat.pois.pid_search}
           thuecat_trail_show = {$thuecat.trails.pid_show}
       }
       itemsPerPage = {$thuecat.settings.itemsPerPage}
   }

Content elements
================

Register one content element per plugin in your own extension or sitepackage, for example with
:ref:`content_blocks:start`. Each content element wires its ``CType`` to a plugin of the ``ThueCat``
extension.

.. include:: _Snippets/PluginWiring.rst.txt

A list content element can be narrowed to the storage folder through its :sql:`pages` field.

A full, ready-to-use content element definition is shown in :ref:`content-element-example`.

Templates
=========

The shipped stubs under :file:`EXT:thuecat/Resources/Private/Templates/` are registered at
:typoscript:`templateRootPaths.10`. Override them by adding a higher index:

.. code-block:: typoscript
   :caption: EXT:myextension/Configuration/Sets/ThuecatAttraction/setup.typoscript

   plugin.tx_thuecat.view {
       templateRootPaths.20 = EXT:my_extension/Resources/Private/Templates/
       partialRootPaths.20 = EXT:my_extension/Resources/Private/Partials/
       layoutRootPaths.20 = EXT:my_extension/Resources/Private/Layouts/
   }

The templates are

* :file:`TouristAttraction/List`
* :file:`TouristAttraction/ListItem` - one list item, cached on its own, see
  :ref:`frontend-output-caching`
* :file:`TouristAttraction/SelectedList`
* :file:`TouristAttraction/SearchForm`
* :file:`TouristAttraction/Show`
* :file:`Trail/ListItem` - one list item, cached on its own, see :ref:`frontend-output-caching`
* :file:`Trail/SelectedList`
* :file:`Trail/Show`

Search and filter
=================

Search and list on one page
---------------------------

The search-and-filter form adapts to what shares its page:

* **With a list (plain or filtered) on the page** the form posts to the same page and the list
  re-renders with the result.
* **Without a list on the page** the form targets the configured search result page
  (:typoscript:`page.pid.thuecat_attraction_search`).
* **On a filtered list** the preset fields are not shown in the search form, but rendered as hidden
  fields to preserve the pre-selection. The visitor refines the remaining fields but cannot widen
  past the preset.

After a search the form re-populates with the submitted values, so the visitor keeps their input.

Filtered list
-------------

A filtered list is the list plugin with a preset in its FlexForm, for example a fixed set of towns.
Visitors refine within the preset but never widen it. The preset fields are described in
:ref:`frontend-output-plugin-settings-presets`.