.. include:: ../../Includes.txt
.. _developers-import-record-kind:

====================
Adding a record kind
====================

A record kind is one upstream ``@type`` imported into one table. This page walks through what a new
one needs, using the smallest existing kind, the town, as the example.

.. contents:: On this page
   :local:
   :depth: 1

The town as an example
====================

:php:`WerkraumMedia\ThueCat\Import\Parser\Entity\TownEntity` writes into :sql:`tx_thuecat_town`. It

* claims nodes whose ``@type`` contains ``schema:City``;
* reads ``schema:name`` and ``schema:description`` once for the default language and once per
  translation language;
* builds its address as an inline child record;
* records ``thuecat:managedBy`` as a reference for the resolver to turn into a relation.

Everything else a town needs, the import does on its own: finding the existing row, writing
translations, logging the saved record.

The table
=========

The table is an ordinary TYPO3 table with TCA. It needs:

* a :sql:`remote_id` column, see :ref:`developers-import-records`;
* the usual language columns, if the kind is translatable — the translations are created with the
  DataHandler's ``localize`` command and need them;
* a column per imported value, named as the entity's property.

Image fields are FAL fields; set ``allowLanguageSynchronization`` on them so translations keep
following the default language.

The entity
==========

An entity extends :php:`WerkraumMedia\ThueCat\Import\Parser\Entity\AbstractEntity`, or
:php:`AbstractPlaceEntity` for a kind that has an address.

Which nodes it parses
   :php:`handlesTypes()` lists the ``@type`` values it claims. A node usually carries several types
   — a tourist information is also a place, an organisation is also a thing. When several entities
   claim the same node, the highest :php:`getPriority()` wins. The default is 10; the more specific
   kinds use 20 or 30. Check which existing entities claim the same types before choosing one.

Where it writes
   The :php:`TABLE` constant names the table. Each property becomes a column of the same name, so
   declare every property with its default value.

What it reads
   :php:`parse()` receives the node, the default language and the translation languages. It fills
   the properties for the default language and records each translated value with
   :php:`recordTranslation()`. Empty values are dropped; see :ref:`developers-import-records` for
   what that means for values upstream clears.

What it cannot resolve itself
   Relations to other upstream objects are recorded as references with :php:`recordTransient()`,
   media with :php:`recordMediaTransient()` and keywords with :php:`recordKeywords()`. See
   :ref:`developers-import-relations`.

What it builds along the way
   Rows built from nested data — addresses, opening hours, dates — are separate entities returned by
   :php:`getChildren()`. Such an entity claims no type of its own, so the parser never picks it for
   a node; only its parent creates it.

Registration is automatic: every class implementing
:php:`WerkraumMedia\ThueCat\Import\Parser\Entity\EntityInterface` carries the ``import.entity`` tag
through the interface, and the parser receives all of them.

Can the kind be a root?
=======================

A kind is either imported on its own, as a root of an import configuration, or only reached as a
relation of something else. Towns are only reached as relations; attractions, events and trails are
roots.

A kind that can be a root implements
:php:`WerkraumMedia\ThueCat\Import\Parser\Entity\TopLevelEntityInterface` and names its **anchor
scope**: the name its category and keyword settings are read under, for example ``trails`` for
:sql:`tx_thuecat_trail`. The scope is declared, not derived from the class name, because integrators
already configured the names that exist.

A new scope needs its settings, one pair per tree the kind fills: :yaml:`import.<scope>.category.*`
if it has categories, :yaml:`import.<scope>.keywords.*` if it has keywords. They go into the site
set definition, grouped under a category of their own, and as ``import<Scope>…`` keys into
:file:`ext_conf_template.txt`. Trails, for example, have keywords only and therefore two settings.
Until the settings exist, the kind falls back to the ``thuecat`` scope. See
:ref:`developers-import-anchors`.

A kind reached only as a relation implements nothing extra and uses the ``thuecat`` scope.

Relations to and from the kind
==============================

Other kinds point at the new one only where the resolver knows the target table and the field to
write. That is :php:`Resolver::BUCKET_MAP`, explained in :ref:`developers-import-relations`. Adding
a target table there also means adding its field to the TCA of every owner table.

In the backend, relation fields pointing at the new table should only offer records of the current
site. Check whether the table belongs in the site-scoped selects, see
:ref:`developers-backend-relation-scoping-new-record-kind`. Leaving it out does not fail; the field
silently offers records of every site.

Checklist
=========

#. Table with :sql:`remote_id`, language columns and TCA.
#. Entity with :php:`TABLE`, typed properties with defaults, :php:`handlesTypes()` and, if types
   overlap, a priority.
#. :php:`TopLevelEntityInterface` and the scope's settings, if the kind can be a root.
#. :php:`BUCKET_MAP` entries and owner fields, if other kinds relate to it.
#. Site-scoped selects in the backend.
#. A functional test importing one fixture of the kind, and one re-importing it, see
   :ref:`developers-import-testing`.