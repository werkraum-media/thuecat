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
:doc:`/ContentElementExample`.

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

Further topics
==============

Some fields carry richer data than a single value and have their own model
accessors and partials; rendered output is cached. These are covered on their own
pages:

.. toctree::
   :maxdepth: 1
   :titlesonly:

   OpeningHours
   Media
   Caching

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

.. _frontend-output-filter-fields:

Registering a filter field
==========================

Which filters the search mask offers is not a list in the controller. A filter
field is a tagged service implementing
:php:`WerkraumMedia\ThueCat\Service\FilterField\FilterFieldDefinition`, tagged
``search.filter.field``.
:php:`WerkraumMedia\ThueCat\Service\SearchFilterOptionsService` discovers every
implementation through that tag and builds it. Another extension adds a filter
without editing any class here.

Two storage shapes are supported, each with an abstract base to extend. Pick the
one matching where the values live. Both live in
:file:`Classes/Service/FilterField/`; the examples are the shipped fields, in the
same directory.

Values in a comma-separated uid column on the record itself, i.e. a TCA select
without ``MM`` — base
:php:`WerkraumMedia\ThueCat\Service\FilterField\CommaColumnField`:

.. code-block:: php

   <?php

   namespace Vendor\Extension\Service\FilterField;

   use WerkraumMedia\ThueCat\Service\FilterField\CommaColumnField;

   final class TownFilterField extends CommaColumnField
   {
       public function __construct()
       {
           parent::__construct(
               name: 'towns',
               recordColumn: 'town',
               optionTable: 'tx_thuecat_town',
           );
       }
   }

Values in an MM table over a parent/child option table, offered as the tree below
a configured anchor — base
:php:`WerkraumMedia\ThueCat\Service\FilterField\HierarchicalMmField`:

.. code-block:: php

   <?php

   namespace Vendor\Extension\Service\FilterField;

   use WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting;
   use WerkraumMedia\ThueCat\Service\FilterField\HierarchicalMmField;

   final class CategoryFilterField extends HierarchicalMmField
   {
       public function __construct()
       {
           parent::__construct(
               name: 'categories',
               mmTable: 'sys_category_record_mm',
               mmFieldName: 'categories',
               optionTable: 'sys_category',
               parentColumn: 'parent',
               anchorSetting: CategoryAnchorSetting::CategoryParent,
           );
       }
   }

With ``autoconfigure`` enabled the tag is applied by the interface, so no entry
in :file:`Services.yaml` is needed.

What each argument means
------------------------

``name``
   The demand property and the view key. The template binds the option set under
   this name, so a field named ``towns`` is rendered from ``{towns}``. It is not
   the MM field name: the two happen to coincide today but answer to different
   owners.

``recordColumn``
   The column holding the comma-separated uids. A record without a value carries
   the placeholder ``0``, which is never offered as an option.

``mmFieldName``
   Which field of a shared MM table the relation belongs to. Categories and
   keywords share :sql:`sys_category_record_mm` and are told apart by this alone,
   so it is part of every query.

``anchorSetting``
   A case of
   :php:`WerkraumMedia\ThueCat\Import\Settings\CategoryAnchorSetting`, naming the
   site setting that holds the category the offered tree starts below. The
   declaration carries the *setting*, never a resolved uid — anchors are per site
   and are resolved per request. An unconfigured anchor offers nothing.

The record table is deliberately absent. It belongs to the scope, not to the
field, so one field definition serves every record kind that offers it.

What a field does not decide
----------------------------

A field declares where its values are read from and nothing else. Scoping is
resolved once per request and applied to every field alike:

* Only values carried by records the sibling list can return are offered. With
  no list on the page, each field offers what its storage holds.
* Values are bound to the site. A value living outside the list's storage pages
  is still offered; one belonging to another site is not.
* Option sets never depend on the visitor's current selection. Choosing one town
  does not remove the others from the form.

Adding a further storage shape
------------------------------

If neither base fits, the shape needs its own provider: implement
:php:`WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\FilterOptionProvider`,
tagged ``search.filter.option.provider``, returning ``true`` from
:php:`supports()` for the field class it reads. Providers are discovered by that
tag, so the service picks one up without being edited.

Extending
:php:`WerkraumMedia\ThueCat\Service\FilterField\OptionProvider\AbstractOptionProvider`
supplies the restriction handling every option query needs — deleted, disabled,
language, storage pages and the site bound — which is easy to get subtly wrong
when writing a provider from scratch. The shipped providers,
:php:`CommaColumnOptionProvider` and :php:`HierarchicalOptionProvider`, sit in the
same namespace as worked examples.

.. _frontend-output-keywords:

Filtering by keyword
====================

Places can be filtered by the :ref:`keywords <import-keywords>` the import stored
on them. The filter behaves like the category filter beside it: selecting several
keywords widens the result, while adding a town or a category narrows it.

Which keywords a plugin offers is chosen **per plugin**, in the FlexForm of the
attraction list. A plugin with no keyword selected offers no keyword control and
applies no keyword constraint, so existing plugins keep behaving as they did.

The selection is bounded by the site's keyword anchor: the field offers the terms
below :ref:`import.thuecat.keywords.parent <import-category-based-anchors>` and
nothing else, so the type-category tree cannot be picked here by mistake. A site
that configures no keyword anchor therefore offers nothing to choose.

Because the anchor belongs to the site, one site running several imports into the
same keyword parent shares one vocabulary across its plugins. Pointing separate
imports at separate keyword parents gives each list its own.

The chosen keywords survive pagination, and the filter mask shows them grouped by
their parent set so long vocabularies stay navigable.

Keyword meta tags
=================

A place detail view emits a ``keywords`` meta tag built from the record's related
keyword categories, joined by ``", "``. A place with no keyword relations emits no
keyword meta tag.

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