.. include:: ../../Includes.txt
.. _frontend-output-plugin-settings:

===============
Plugin settings
===============

Everything a content element can configure for its plugin, field by field. Which content element
offers which field is up to you; :ref:`content-element-example` shows a typical set.

.. contents:: Table of contents
   :local:

.. _frontend-output-plugin-settings-source:

Record source
=============

:sql:`pages`
   The storage folders a list reads from. Left empty, the list is not limited to any storage page.

``recursive``
   How many levels below the folders in :sql:`pages` are included.

Both are core fields of the content element, not FlexForm settings.

.. _frontend-output-plugin-settings-pages:

Target pages and page size
==========================

:typoscript:`settings.itemsPerPage`
   Items per page of a list. Falls back to ``20`` when nothing usable is set.

:typoscript:`settings.page.pid.thuecat_attraction_show`
   Page carrying the attraction detail content element; list items link there.

:typoscript:`settings.page.pid.thuecat_attraction_search`
   Search result page; search forms on pages without a list submit there.

:typoscript:`settings.page.pid.thuecat_trail_show`
   Page carrying the trail detail content element; trail list items link there.

These are usually set once for the whole site, see :ref:`frontend-output-site-settings`. A content
element may override them in its FlexForm.

.. important::

   Extbase lets a FlexForm field override the TypoScript setting even when the field is empty. An
   empty ``page.pid`` field then removes the link target the site settings provide. List every
   overridable field in ``ignoreFlexFormSettingsIfEmpty`` so an empty field falls back to the site
   settings:

   .. code-block:: typoscript
      :caption: EXT:myextension/Configuration/Sets/MySet/setup.typoscript

      plugin.tx_thuecat {
          ignoreFlexFormSettingsIfEmpty = itemsPerPage, page.pid.thuecat_attraction_show
      }

.. _frontend-output-plugin-settings-sort-order:

Sort order
==========

:typoscript:`settings.sortBy`
   Attraction lists only. ``sorting`` lists the attractions in the order editors arranged them in the
   backend, with the title deciding between equal positions. Any other value, or none, lists them by
   title. A request cannot change the order, and it never appears in URLs.

.. important::

   Backend order suits small storage folders. Existing attractions start out sharing one position,
   and TYPO3 cannot place a record between records that share a position: moving it after one of
   them puts it after all of them. Arrange the records by moving them to the top one at a time, last
   one first. Saving the content element applies a changed sort order at once; re-sorting records
   needs a cache flush, see :ref:`frontend-output-caching-invalidation`.

.. _frontend-output-plugin-settings-records:

Picking records
===============

:typoscript:`settings.selectedRecords`
   Selected lists only: the records to show, in the order the editor picked them. Offer records of
   the default language; translations are resolved when rendering.

:typoscript:`settings.selectedRecord`
   Detail views only: pins the record the element shows. When set, the URL parameter is ignored, so
   no link can change what the page renders. Left empty, the record is taken from the URL, which is
   what links from list items rely on. A pinned record that is hidden or deleted renders the "no
   data" message instead of falling back to the URL.

   Do not pin a record on a page that list items link to as detail page: every link would show the
   pinned record.

.. _frontend-output-plugin-settings-presets:

Presets
=======

A list with a preset becomes a filtered list. A preset restricts the list on every request, and the
search-and-filter form on the same page does not show a control for a preset field. It carries the
preset values as hidden fields instead. Visitors can narrow the result with the remaining fields,
but never widen it, not even with a changed URL.

:typoscript:`settings.towns`
   Records in any of the selected towns.

:typoscript:`settings.categories`
   Records related to any of the selected categories or to a category below them.

:typoscript:`settings.keywords`
   Records related to any of the selected keywords or to a keyword below them, see `Keywords`_.

:typoscript:`settings.petsAllowed`, :typoscript:`settings.isAccessibleForFree`,
:typoscript:`settings.publicAccess`
   Checkboxes. When set, only records marked accordingly are listed.

Several values within one field widen the result; several fields narrow it.

.. _frontend-output-plugin-settings-trees:

Category and keyword trees
==========================

:typoscript:`settings.categories` and :typoscript:`settings.keywords` select :sql:`sys_category`
records. Start their tree at the parent category the import writes into, so editors are offered that
tree and not every category of the installation:

.. code-block:: yaml
   :caption: EXT:myextension/ContentBlocks/ContentElements/attraction-list-filtered/config.yaml

   - identifier: 'settings.categories'
     type: 'Select'
     relationship: 'oneToMany'
     renderType: 'selectTree'
     foreign_table: 'sys_category'
     treeConfig:
       parentField: 'parent'
       startingPoints: '###THUECAT_ANCHOR:tx_thuecat_tourist_attraction:category###'

The marker reads ``###THUECAT_ANCHOR:<table>:<category|keywords>###``:

``<table>``
   The record table the content element lists, for example :sql:`tx_thuecat_tourist_attraction` or
   :sql:`tx_thuecat_trail`.

``category`` or ``keywords``
   Which of the two trees.

In the backend form the marker is replaced by the parent category configured for that record kind in
the site of the edited content element, see :ref:`configuration-category-anchors`. Where none is
configured, the tree starts at the root and offers every category.

Core's ``###SITE:…###`` marker works as well, but reads site settings only and never falls back to
the Extension Configuration.

In the search form
   The search-and-filter form offers the keyword tree below the keyword parent category, grouped by
   the keyword sets ThueCat defines. It offers only keywords carried by records the list on the page
   can return. A site without a keyword parent category offers no keyword choice.

   Selecting several keywords widens the result; adding a town or a category narrows it. Selecting a
   keyword set includes all children below it. The selection survives pagination.

As a preset
   :typoscript:`settings.keywords` restricts a list to the selected keywords, see `Presets`_. The
   search form then shows no keyword control.

On the detail view
   The attraction and trail detail views emit a ``keywords`` meta tag built from the record's
   keywords, joined by ``", "``. A record without keywords emits none.

Events
   Imported events carry keyword relations as well, but no frontend output of this extension reads
   them.