.. include:: ../Includes.txt
.. _developers-detail-record-kinds:

===================
Detail record kinds
===================

A detail record kind is a table whose records have a detail page of their own: tourist attractions
and trails today. The kinds are listed in one registry,
:php:`WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKinds`. The slug upgrade wizard and
the XML sitemap ask it instead of naming a table, so a kind added by another package reaches them.

.. contents:: On this page
   :local:
   :depth: 1

.. _developers-detail-record-kinds-what-a-kind-declares:

What a kind declares
====================

A kind is a :php:`WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKind`:

.. list-table::
   :header-rows: 1

   * - Property
     - Example
     - Purpose
   * - ``identifier``
     - ``attraction``
     - Stable key, used as ``config.kind`` of the sitemap.
   * - ``table``
     - ``tx_thuecat_tourist_attraction``
     - The table holding the records.
   * - ``extensionName``
     - ``ThueCat``
     - Extbase extension name of the show plugin.
   * - ``pluginName``
     - ``TouristAttractionShow``
     - Extbase plugin rendering one record.
   * - ``argumentName``
     - ``attraction``
     - The show action's record argument.
   * - ``showPageSetting``
     - ``thuecat.pois.pid_show``
     - Site setting naming the canonical detail page; 0 or unset means none.

.. _developers-detail-record-kinds-registering-a-kind:

Registering a kind
==================

Implement :php:`WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKindProvider` in an
autoconfigured service of your package. The interface carries the service tag, so nothing else is
registered:

.. code-block:: php
   :caption: EXT:myextension/Classes/DetailRecordKind/MeetingPlaceKind.php

   namespace MyVendor\MyExtension\DetailRecordKind;

   use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKind;
   use WerkraumMedia\ThueCat\Domain\DetailRecordKind\DetailRecordKindProvider;

   final class MeetingPlaceKind implements DetailRecordKindProvider
   {
       public function getKind(): DetailRecordKind
       {
           return new DetailRecordKind(
               'meetingplace',
               'tx_myextension_meeting_place',
               'MyExtension',
               'MeetingPlaceShow',
               'meetingPlace',
               'myextension.meetingplaces.pid_show',
           );
       }
   }

.. code-block:: yaml
   :caption: EXT:myextension/Configuration/Services.yaml

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     MyVendor\MyExtension\:
       resource: '../Classes/*'

.. _developers-detail-record-kinds-what-a-new-kind-ships-alongside:

What a new kind ships alongside
===============================

The registry entry makes the kind known; each capability still needs its own file:

Slug column
   A ``slug`` column of TCA type ``slug``, generated from the title, ``eval`` ``uniqueInSite``,
   ``prependSlash`` false. New records get their slug on insert; the upgrade wizard fills existing
   ones, see :ref:`maintenance-slugs`.

Route file
   A route enhancer YAML for the show plugin, like
   :file:`EXT:thuecat/Configuration/Routes/TouristAttractionShow.yaml`: an ``Extbase`` enhancer with
   ``routePath: '/{<argument>}'``, a ``PersistedAliasMapper`` on ``slug``, and ``limitToPages`` on
   the page role. Sites import it, see :ref:`frontend-output-routing`.

Page role item
   An item for :sql:`pages.tx_thuecat_page_role`, added through a TCA override with
   :php:`ExtensionManagementUtility::addTcaSelectItem()`, whose value the route file's
   ``limitToPages`` names.

Page role mapping
   One ``tx_thuecat.pageRoles`` line for the content element type rendering the show plugin, in the
   package that defines the content element.

Sitemap block
   One block under ``plugin.tx_seo.config.xmlSitemap.sitemaps`` with ``config.kind`` set to the
   identifier, see :ref:`frontend-output-xml-sitemap`.

Show template markup
   The structured data of the detail page, in the template of the package that renders it.
