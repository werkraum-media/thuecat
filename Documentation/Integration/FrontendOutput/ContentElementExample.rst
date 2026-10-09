.. include:: ../../Includes.txt
.. _content-element-example:

=======================
Content element example
=======================

A complete, ready-to-use content element built with :ref:`content_blocks:start`. This example is the
*selected list*: an editor picks a fixed set of attractions, rendered in the chosen order. Replace
``myvendor`` with your own vendor name.


.. code-block:: yaml
   :caption: EXT:myextension/ContentBlocks/ContentElements/attraction-list-selected/config.yaml

   name: myvendor/attraction-list-selected
   group: Thuecat Attraction
   prefixFields: true
   prefixType: vendor
   fields:
     - identifier: TYPO3/Header
       type: Basic
     - identifier: 'pi_flexform'
       type: 'FlexForm'
       useExistingField: true
       fields:
         - identifier: 'settings.selectedRecords'
           type: 'Select'
           renderType: 'selectMultipleSideBySide'
           foreign_table: 'tx_thuecat_tourist_attraction'
           foreign_table_where: 'AND {#tx_thuecat_tourist_attraction}.{#sys_language_uid} IN (0, -1)'

.. include:: _Snippets/PluginWiring.rst.txt

The :typoscript:`settings.selectedRecords` FlexForm field lets the editor choose the attractions;
the ``TouristAttractionListSelected`` plugin renders them via the ``SelectedList`` template in the
picked order.

A selected list of trails is the same content element with :sql:`tx_thuecat_trail` as
``foreign_table`` and ``TrailListSelected`` as plugin name.

The other content elements follow the same shape, wired to their plugin name, with these fields:

.. list-table::
   :header-rows: 1

   * - Content element
     - Plugin name
     - Fields
     - Flexform Fields
   * - List
     - ``TouristAttractionList``
     - :sql:`pages`, ``recursive``
     - :typoscript:`settings.itemsPerPage`, :typoscript:`settings.page.pid.thuecat_attraction_show`,
       :typoscript:`settings.sortBy`
   * - Filtered list
     - ``TouristAttractionList``
     - same as the list
     - same as the list, plus :typoscript:`settings.towns`, :typoscript:`settings.categories`,
       :typoscript:`settings.keywords`
   * - Search and filter
     - ``TouristAttractionSearch``
     - none
     - none
   * - Attraction detail
     - ``TouristAttractionShow``
     - none
     - :typoscript:`settings.selectedRecord` (makes the plugin show the selected record, don't use
       as a link target for list pages)
   * - Trail detail
     - ``TrailShow``
     - none
     - :typoscript:`settings.selectedRecord` (makes the plugin show the selected record, don't use
       as a link target for list pages)

What each field does, and how to start the category and keyword trees at the right parent category,
is described in :ref:`frontend-output-plugin-settings`.