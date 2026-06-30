.. _frontend-output:

===============
Frontend output
===============

EXT:thuecat registers Extbase controller actions and ships template stubs, but
**no content elements**. You define the content elements in your own extension or
sitepackage and point them at the registered plugins, so rendering stays under
your control.

Plugins and templates
=====================

The extension name is ``ThueCat``. Five content elements are intended, built from
four plugins (the filtered list is the list plugin with an editor preset). Each
plugin renders one template:

==================================  =================  =================
Plugin name                         Content element    Template
==================================  =================  =================
``TouristAttractionList``           List               ``List``
``TouristAttractionList``           Filtered list      ``List``
``TouristAttractionListSelected``   Selected list      ``SelectedList``
``TouristAttractionSearch``         Search-and-filter  ``SearchForm``
``TouristAttractionShow``           Detail             ``Show``
==================================  =================  =================

The plain and filtered list are the same ``TouristAttractionList`` plugin and
therefore share the ``List`` template; the filtered variant only adds an editor
preset in its FlexForm (see `Filtered list`_). The search element is a combined
**search and filter** form.

Required pages
==============

Set up these pages once and configure their ids (see `Site settings`_):

* **Storage folder** -- a sysfolder holding the tourist attraction records.
* **Detail page** -- carries the detail content element; target for links to a
  single attraction.
* **List / search page** -- carries **both** the list content element and the
  search-and-filter content element.

On the list / search page neither plugin is optional: the search-and-filter form
submits into the list, and the list renders the result. A page with only one of
the two does not work.

Content elements
================

Register one content element per plugin in your own extension or sitepackage, for
example with `Content Blocks <https://docs.typo3.org/permalink/friendsoftypo3-content-blocks:start>`_. Each content element
wires its ``CType`` to a plugin via the ``ThueCat`` extension name:

.. code-block:: typoscript

   tt_content.myvendor_attractionlist =< lib.contentBlock
   tt_content.myvendor_attractionlist {
       20 =< lib.contentBlock.20
       20 {
           pluginName = TouristAttractionList
       }
   }

The list content element points at the storage folder through its ``pages`` field.

A full, ready-to-use content element definition is shown in
:doc:`ContentElementExample`.

Templates
=========

The shipped stubs under ``EXT:thuecat/Resources/Private/Templates/`` are
registered at ``templateRootPaths.10``. Override them by adding a higher index:

.. code-block:: typoscript

   plugin.tx_thuecat.view {
       templateRootPaths.20 = EXT:my_extension/Resources/Private/Templates/
       partialRootPaths.20 = EXT:my_extension/Resources/Private/Partials/
       layoutRootPaths.20 = EXT:my_extension/Resources/Private/Layouts/
   }

The templates are

* ``TouristAttraction/List``
* ``TouristAttraction/SelectedList``
* ``TouristAttraction/SearchForm``
* ``TouristAttraction/Show``

Field-specific output
=====================

Some fields carry richer data than a single value and have their own model
accessors and partials. They are covered on their own pages:

.. toctree::
   :maxdepth: 1
   :titlesonly:

   OpeningHours
   Media

Site settings
=============

Provide a site set that maps site settings onto the plugin configuration and
include it in your site. Fill in the page ids under :guilabel:`Settings` in the
site configuration.

Settings definition (``Configuration/Sets/<YourSet>/settings.definitions.yaml``):

.. code-block:: yaml

   settings:
     page.pid.thuecat_attraction_show:
       label: 'Detail Page for Tourist Attractions'
       description: 'The page providing the detail pages for tourist attractions'
       category: 'page.pids'
       type: 'int'
       default: 0
     page.pid.thuecat_attraction_search:
       label: 'Search Result Page for Tourist Attractions'
       description: 'The page providing the list of tourist attractions, used as target for search form submissions'
       category: 'page.pids'
       type: 'int'
       default: 0
     list.itemsPerPage:
       label: 'Tourist Attractions per Page'
       description: 'Number of tourist attractions shown per page in the list view'
       category: 'list'
       type: 'int'
       default: 20

Mapping (``Configuration/Sets/<YourSet>/setup.typoscript``):

.. code-block:: typoscript

   plugin.tx_thuecat.settings {
       page.pid {
           thuecat_attraction_show = {$page.pid.thuecat_attraction_show}
           thuecat_attraction_search = {$page.pid.thuecat_attraction_search}
       }
       itemsPerPage = {$page.settings.itemsPerPage}
   }

Search and list on one page
===========================

The search-and-filter form adapts to what shares its page:

* **With a list (plain or filtered) on the page** the form posts to the same page
  and the list re-renders with the result.
* **Without a list on the page** the form targets the configured list / search
  page (``page.pid.thuecat_attraction_search``).
* **On a filtered list** the preset fields are not shown in the search form, but rendered as hidden fields to preserve
  the pre-selection. The
  visitor refines the remaining fields but cannot widen past the preset.

After a search the form re-populates with the submitted values, so the visitor
keeps their input.

Filtered list
=============

A filtered list carries an editor preset in its FlexForm (for example a fixed set
of towns). The list re-applies the preset on every request: a visitor search
refines *within* the preset but can never widen it, even with a tampered URL.

How it works
============

The search-and-filter form does not read its own settings to learn the preset. A
resolver inspects the current page for a list content element, reads its FlexForm,
and returns the active preset:

* No list on the page -- the form targets the configured list / search page.
* A list on the page -- the form stays on the page; a filtered list additionally
  contributes the preselected fields.

The lookup is language- and overlay-aware, because it reads the stored content
element through the frontend's record retrieval.

The form is rendered by ``TouristAttractionSearch`` but submits under
``TouristAttractionList``, so the demand travels in the list's namespace. On
re-render the form adopts that demand to re-populate its fields, and the editor
preset is forced onto the locked fields, so the hidden inputs always carry the
editor's values.