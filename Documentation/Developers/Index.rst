.. include:: ../Includes.txt
.. _developers:

==============
For Developers
==============

Information about how the extension is built, how to extend it and how changes are checked.

.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :card-height: 100

   .. card:: :ref:`developers-import`

      The concepts the import is built on and the rules a change has to keep: adding a record kind,
      a property or a relation, a new import source, and how to test it.

   .. card:: :ref:`developers-detail-record-kinds`

      The registry of tables whose records have a detail page of their own, and what a kind added by
      another package has to declare to reach the slug upgrade wizard and the XML sitemap.

   .. card:: :ref:`developers-filter-fields`

      How the search-and-filter form of tourist attractions is built from tagged filter fields, and
      how to add one without editing the extension.

   .. card:: :ref:`developers-backend-relation-scoping`

      How the site scoping of relation fields is wired into TCA and FlexForm, and what to decide when
      a record kind is added.

   .. card:: :ref:`developers-quality-assurance`

      The tools that check every change: coding guideline, static analysis, layer rules, tests,
      coverage and mutation testing, and the baseline none of them may fall below.

.. toctree::
   :hidden:

   Import/Index
   DetailRecordKinds
   FilterFields
   BackendRelationScoping
   QualityAssurance