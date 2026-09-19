.. _backend-relation-scoping:

========================
Backend relation scoping
========================

Relation fields in the backend offer only records belonging to the site of the record
being edited. An installation holding several sites imports the same kinds of records
into each of them, and without scoping every picker lists all of them at once: editing an
attraction in one site, the :guilabel:`Town` field offered the towns another site's import
had written, with nothing in the label to tell them apart. Picking one produced a relation
across site boundaries that the frontend then had to survive.

The scope covers both surfaces of a relation field — the dropdown and the type-ahead
suggest wizard — and applies to content element fields selecting ThueCat records as well.

.. _backend-relation-scoping-what-is-offered:

What is offered
===============

A relation field offers a record when both hold:

Stored within the site
   The record sits on a page belonging to the site of the record being edited. A record
   being newly created is scoped by the site of the page it is created on, exactly as it
   will be after saving.

In the default language
   Translations share their original's storage page, so site scope alone cannot separate
   them and a field would otherwise offer every record once per language, each entry
   carrying the same title. Records stored with the "all languages" marker stay
   selectable — that means *valid everywhere*, not *some particular language*.

Which pages count as belonging to the site is answered by the same service the import
uses for the same question, so a record the import treats as in scope is a record the
backend offers. Adding or removing a page from a site changes both together, with no
configuration to edit.

Scope follows **where a record is stored**, not whether that page is visible in the
frontend: a record on a hidden storage folder, or one past its publication end, is still
offered. Only deleted pages fall out of scope.

.. _backend-relation-scoping-coverage:

Which fields are scoped
=======================

A field is scoped when it is a :php:`select` field whose :php:`foreign_table` is one of
the tables holding ThueCat records:

* :sql:`tx_thuecat_town`
* :sql:`tx_thuecat_organisation`
* :sql:`tx_thuecat_tourist_attraction`
* :sql:`tx_thuecat_tourist_information`
* :sql:`tx_thuecat_parking_facility`
* :sql:`tx_thuecat_trail`

Fields are matched by that rule rather than listed, so a relation field added later is
scoped without anyone remembering to register it. No TCA file is edited to achieve this;
the condition is written into the fields as TCA is compiled.

Not affected: the translation parent fields (:sql:`l10n_parent` / :sql:`l18n_parent`, which
core manages), fields with a static list of items and no foreign table, the import log
relations, and the category and keyword fields. The latter are :php:`type => category`
and already bounded by the site's anchors — see :ref:`import-category-based-anchors`.

.. important::

   The table list above is the statement of what counts as a ThueCat record, and it is
   maintained by hand, in :php:`WerkraumMedia\ThueCat\Service\SiteScopedSelectFields`.

   **When a new record kind is introduced, decide whether its table belongs in that
   list.** Nothing detects the omission: a relation field pointing at a table missing
   from it keeps working and simply offers the whole installation, in every site, on both
   the dropdown and the suggest wizard. That is the behaviour this page exists to prevent,
   and it returns silently.

   Adding the table is the usual answer, but not automatic — a table deliberately shared
   across sites, or one never used as a relation target, is correctly left out. What
   matters is that the question is asked when the record kind is added, rather than
   noticed by an editor later.

.. _backend-relation-scoping-suggest-wizard:

The suggest wizard
==================

The type-ahead wizard behind a relation field is bounded by the same page set as that
field's dropdown, so neither surface offers what the other hides. Scoping restricts what
the wizard finds; it stays available wherever it was configured.

.. warning::

   The wizard is bounded through a single page TSconfig block,
   :typoscript:`TCEFORM.suggest.default.addWhere`, shipped in this extension's
   :file:`Configuration/page.tsconfig`.

   :php:`SuggestWizardController` honours a field's own :php:`foreign_table_where` **only
   while TSconfig sets no** :typoscript:`addWhere`. Because that block carries no table
   name, it applies to every suggest field in the installation and therefore disables
   that fallback **installation-wide — including for tables this extension does not
   own**. A third-party relation field that relied on its :php:`foreign_table_where` to
   bound its wizard will find that clause ignored once this extension is installed.

   This is why the default-language condition is folded into the block rather than left
   to the individual fields: the content element fields that carry it in their own
   :php:`foreign_table_where` would otherwise lose it on the wizard while keeping it on
   the dropdown, and the two surfaces would disagree.

.. _backend-relation-scoping-limits:

Limits
======

Records stored outside any site are offered by no field, because they belong to no scope
to be offered within.

Existing relations are not touched. This decides what is offered from now on; a
cross-site relation stored before the scoping existed keeps whatever it holds until an
editor changes it.
