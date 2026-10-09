.. include:: ../../Includes.txt
.. _developers-import-categories:
.. _importing-sys-category-based-values:
.. _sys-category-fields:

===============================
Values stored as category trees
===============================

Some imported values are stored as :sql:`sys_category` records instead of text: the categories
derived from an object's ``@type``, and its keywords. Editors can then select them in the backend,
and the frontend can filter by them.

The two are different properties that happen to share a table. They should never share a tree, a
record or an identifier, even where a keyword and a category carry the same title.

.. list-table::
   :header-rows: 1

   * - Property
     - Source
     - Field on the owner
     - Identifier prefix
   * - Categories
     - ``@type``, through a mapping of known types to titles
     - ``categories``
     - ``type:``
   * - Keywords
     - ``schema:keywords``
     - ``keywords``, on events :sql:`keywords_relation`
     - ``keyword:``

.. contents:: On this page
   :local:
   :depth: 1

.. _developers-import-anchors:
.. _import-category-based-anchors:

Where a tree lives: anchors and scopes
======================================

Each tree hangs from an **anchor**: a parent category and the folder its records are stored in.
Integrators set them per site, see :ref:`configuration-category-anchors`.

Which anchor a record uses depends on the record's kind, through its **scope**. A kind that can be a
root declares its scope (:ref:`developers-import-record-kind`); all other kinds, and any scope with
nothing configured, use the ``thuecat`` scope.

.. list-table::
   :header-rows: 1

   * - Record kind
     - Scope
     - Trees
   * - Tourist attraction
     - ``thuecat``
     - categories and keywords
   * - Event
     - ``events``
     - categories and keywords
   * - Trail
     - ``trails``
     - keywords
   * - Organisation, town, tourist information, parking facility
     - ``thuecat``
     - as configured there

The scope belongs to the record, not to the run. An attraction import that reaches a trail files the
trail's keywords under ``trails``. Records reached only as relations are shared between imports, so
their trees must not depend on which import reached them.

Every scope is its own tree. Places and events imported into one site share a keyword tree only
where the ``events`` scope is left empty and falls back to ``thuecat``, or where both scopes point
at the same anchor.

Resolution, per scope and per tree:

#. the site settings of the site owning the storage page;
#. the Extension Configuration;
#. nothing — the tree is switched off and the import writes no values for it.

A scope that supplies neither value of a pair (storagePid and parent) falls back to ``thuecat`` as a
whole pair. Half a pair is never completed from another scope: that would pair one tree's parent
with another tree's folder.

The same resolution serves the import, the frontend filters and the category fields in the backend,
so all three offer the tree the import writes into.

Every scope is validated before every run, also scopes the run will not write, because which kinds a
run meets is known only after fetching. A pair set halfway, or an anchor outside the site, stops the
run before anything is fetched.

Creating the records
====================

:php:`WerkraumMedia\ThueCat\Import\SysCategory\SysCategoryProvisioner` turns values into
:sql:`sys_category` records for any such property. The next property of this kind should use it
instead of creating categories itself; separate implementations are how the trees drifted apart
before.

It is given an anchor (:php:`SysCategoryAnchor`: parent, folder, identifier prefix) and a
deduplication state for the run (:php:`SysCategoryProvisioningState`). Both belong to one property.
Sharing either merges two trees.

What it guarantees:

Reuse
   A record is matched by its identifier, never by its title, so an editor may rename an imported
   category and the rename survives. The match only counts if the anchor is in the record's
   rootline; a record in another tree is never taken over.

Moving instead of replacing
   A stored record whose parent changed upstream is moved. Category uids are referenced in plugin
   configuration; a replacement would look identical in the tree and be wrong everywhere it is used.

Translations
   For the languages of the site, where upstream has a title in that language. A language without a
   title is left out rather than filled with the default title.

Skipping
   A value without a title in the default language is not created; an untitled category is worse
   than none. Its children attach to the nearest ancestor that was created.

What it leaves to the caller: what a title means, and which parent a value belongs under.
For ``@type`` the vocabulary answers both, for keywords their term sets do.

A table that receives values but has no field for them is not written and is reported once per run
as ``categoriesFieldMissing``.

.. _category-hierarchy:

The ``@type`` hierarchy
=======================

``@type`` values are classes with a hierarchy of their own: ``schema:Museum`` is a
``CivicStructure``, which is a ``Place``, which is a ``Thing``. The import mirrors that, so editors
get a tree instead of a few hundred flat names.

The hierarchy comes from two vocabularies, fetched whole and merged into one index:

* ``https://schema.org/version/latest/schemaorg-current-https.jsonld``
* ``https://thuecat.org/ontology/thuecat/1.0/?format=jsonld``

ThueCat extends schema.org, so one chain may cross between them; the index needs both. Whole
documents are fetched rather than one class at a time, because the per-class endpoints are rate
limited and a chain would need one request per level.

The index is cached for 14 days. The age is stored with the entry, not left to the cache backend,
because an expired entry is exactly what a failed refresh falls back on. A refresh replaces both
vocabularies or neither; a fresh one paired with a stale one could break every chain crossing
between them. A stale or unavailable vocabulary is logged as ``vocabularyStale`` or
``vocabularyUnavailable``.

Building one chain
------------------

From the type upwards, the classes to create, top first:

Cut-off
   ``schema:Thing`` and ``schema:Place`` get no category. Every imported record is one, so they
   distinguish nothing. A type with nothing left above it hangs directly from the anchor.

Restated parents
   A class naming both ``CivicStructure`` and ``Museum`` as parents, where ``Museum`` is itself a
   ``CivicStructure``, describes one chain, not two. The nearer parent wins.

Real branches
   Where the remaining parents do not meet, one has to be chosen; a tree cannot hold a record twice.
   The choice depends on the owner's kind
   (:php:`WerkraumMedia\ThueCat\Import\SysCategory\ParentStrategies`): attractions prefer a branch
   reaching ``TouristAttraction``, then ``Place``; events prefer ``Event``; other kinds take the
   deepest branch. The choice is logged as ``categoryParentChosen``. A branch reaching no preferred
   root is logged as ``categoryParentUnpreferred`` at warning level, because no rule fits it and a
   person should look.

``TouristAttraction`` and ``Place`` steer the choice without appearing in the tree. They are listed
as ignored values in the mapping, so no category is created for them.

A type the vocabulary does not know still gets its category, directly under the anchor, and is
logged as ``categoryWithoutHierarchy``. Losing it would take away structure editors already have.

Only the types a record names become its relations. Their ancestors exist to give the tree its
levels.

Which types become categories at all is a fixed mapping in :php:`PlaceCategoryMapper` and
:php:`EventCategoryMapper`. Matched and unmatched types are reported per run as ``categoryMatched``
and ``categoryUnmatched``, so a type upstream starts using shows up in the log.

.. _import-keywords:

Keywords
========

``schema:keywords`` mixes a controlled vocabulary of terms, grouped into term sets, with keywords
editors typed by hand. The three upstream shapes and their identities are described in
:ref:`developers-import-relation-sets`.

The term sets become intermediate categories, so a term appears beneath its set instead of in one
flat list. Only the term a record carries becomes a relation; the sets above it organise the tree.
Following a term up to its sets is exempt from the usual fetch depth and has a limit of its own.

Titles are taken from upstream only when a keyword category is created. Renames by editors survive,
as for categories.

Places use their keywords in the frontend, for filtering and meta tags. Events receive keyword
relations, but nothing in the frontend reads them yet.