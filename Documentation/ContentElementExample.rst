.. _content-element-example:

=======================
Content element example
=======================

A complete, ready-to-use content element built with
:doc:`Content Blocks <content_blocks:Index>`. This example is the *selected list*:
an editor picks a fixed set of attractions, rendered in the chosen order. Replace
``myvendor`` with your own vendor name.

Definition (``ContentBlocks/ContentElements/attraction-list-selected/config.yaml``):

.. code-block:: yaml

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

Wiring (``ContentBlocks/ContentElements/attraction-list-selected/setup.typoscript``):

.. code-block:: typoscript

   tt_content.myvendor_attractionlistselected =< lib.contentBlock
   tt_content.myvendor_attractionlistselected {
       20 =< lib.contentBlock.20
       20 {
           pluginName = TouristAttractionListSelected
       }
   }

The ``settings.selectedRecords`` FlexForm field lets the editor choose the
attractions; the ``TouristAttractionListSelected`` plugin renders them via the
``SelectedList`` template in the picked order.

The other content elements follow the same shape: a FlexForm field for their
settings (a town preset for the filtered list, none for the plain list, none for
the search-and-filter form) wired to the matching plugin name from
:doc:`FrontendOutput/Index`.