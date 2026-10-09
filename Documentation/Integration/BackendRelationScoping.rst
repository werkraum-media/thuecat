.. include:: ../Includes.txt
.. _backend-relation-scoping:

========================
Backend relation scoping
========================

Relation fields in the backend offer only records belonging to the site of the record being edited.
An installation holding several sites imports the same kinds of records into each of them. Without
scoping, every picker lists the records of all sites at once, with nothing in the label to tell them
apart, and picking one produces a relation across site boundaries.

The scope covers both surfaces of a relation field — the dropdown and the type-ahead suggest wizard
— and applies to content element fields selecting ThueCat records as well.

.. _backend-relation-scoping-what-is-offered:

What is offered
===============

A relation field offers a record when both hold:

Stored within the site
   The record sits on a page belonging to the site of the record being edited. A record being newly
   created is scoped by the site of the page it is created on, exactly as it will be after saving.

In the default language
   Translations share their original's storage page, so site scope alone cannot separate them and a
   field would otherwise offer every record once per language, each entry carrying the same title.
   Records stored with the "all languages" marker stay selectable — that means *valid everywhere*,
   not *some particular language*.

Which pages belong to the site is answered the same way as for the import, so a record the import
treats as in scope is a record the backend offers. Adding or removing a page from a site changes
both together, with no configuration to edit.

Scope follows **where a record is stored**, not whether that page is visible in the frontend: a
record on a hidden storage folder, or one past its publication end, is still offered. Only deleted
pages fall out of scope.

.. _backend-relation-scoping-coverage:

Which fields are scoped
=======================

A field is scoped when it is a :php:`select` field whose :php:`foreign_table` is one of the tables
holding ThueCat records:

* :sql:`tx_thuecat_town`
* :sql:`tx_thuecat_organisation`
* :sql:`tx_thuecat_tourist_attraction`
* :sql:`tx_thuecat_tourist_information`
* :sql:`tx_thuecat_parking_facility`
* :sql:`tx_thuecat_trail`
* :sql:`tx_events_domain_model_location`
* :sql:`tx_events_domain_model_organizer`

Fields are matched by that rule rather than listed, so a relation field added in a content element
is scoped without being registered anywhere. No TCA file needs to be edited.

Not affected: the translation parent fields (:sql:`l10n_parent` / :sql:`l18n_parent`, which core
manages), fields with a static list of items and no foreign table, the import log relations, and the
category and keyword fields.

.. note::

   Category and keyword fields are :php:`type => category` and bounded differently: their tree
   starts at the parent category the import writes into for the site. The fields name that parent
   through the ``###THUECAT_ANCHOR###`` marker in their ``startingPoints``, see
   :ref:`frontend-output-plugin-settings-trees`.

.. _backend-relation-scoping-suggest-wizard:

The suggest wizard
==================

The type-ahead wizard behind a relation field is bounded by the same page set as that field's
dropdown, so neither surface offers what the other hides. Scoping restricts what the wizard finds;
it stays available wherever it was configured.

.. warning::

   The wizard is bounded through a single page TSconfig block,
   :typoscript:`TCEFORM.suggest.default.addWhere`, shipped in this extension's
   :file:`Configuration/page.tsconfig`.

   Core uses a field's own :php:`foreign_table_where` for the wizard **only while TSconfig sets no**
   :typoscript:`addWhere`. That block carries no table name, so it applies to every suggest field in
   the installation — **including tables this extension does not own**. A third-party relation field
   relying on its :php:`foreign_table_where` to bound its wizard has that clause ignored while this
   extension is installed.

.. _backend-relation-scoping-limits:

Limits
======

Records stored outside any site are offered by no field, because they belong to no scope to be
offered within.

Scoping decides what a field offers; it does not check stored relations. A relation across site
boundaries that is already stored keeps whatever it holds until an editor changes it.

How the scoping is wired, and what to decide when adding a record kind, is described in
:ref:`developers-backend-relation-scoping`.