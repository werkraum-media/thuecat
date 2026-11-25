.. include:: ../../Includes.txt
.. _developers-import-records:

===============================
Records, identity and languages
===============================

.. contents:: On this page
   :local:
   :depth: 1

How a record is recognised
==========================

Every imported row carries the upstream ``@id`` in its local database :sql:`remote_id` column. On
every run the import looks for a row with that :sql:`remote_id` and updates it, or creates a new one
in the storage page of the import configuration.

The lookup is limited to the **site** that owns the storage page, the whole page tree of it.
Two consequences:

* Two import configurations of the same site recognise each other's records, even when they write
  into different storage folders. An organisation imported by one configuration is updated, not
  duplicated, by the next.
* Two sites importing the same upstream object each get a record of their own. Neither touches the
  other's. This is what keeps sites independent in the frontend and the backend.

Within one job, every upstream object is written at most once by reference, however many roots
reference it. The first item to reach it writes it; later items find it in the database and only
relate to it. An object that is a root of its own is updated by its own item.

Records without an ``@id`` of their own
---------------------------------------

Some rows are produced from data nested inside another object and have no upstream identity: an
address, an opening hours specification, a trail's start location. They get a :sql:`remote_id`
derived from their parent's, for example ``https://thuecat.org/resources/123::addr::0`` for the
first address of record ``123``. A re-import therefore finds the same row again, and a stored row
whose position upstream no longer supplies is removed.

A few tables of :composer:`werkraummedia/events` identify rows differently:

* A location is identified by the :sql:`global_id` hash :composer:`werkraummedia/events` computes
  from its name and address, so an import from ThueCat and one from another source converge on one
  record.
* An organizer has no such hash; the import gives it a :sql:`remote_id`. Organizers without one
  belong to other imports and are never touched.

.. _developers-import-fetch-depth:

How far references are followed
===============================

The upstream data is densely linked: a region links to dozens of places, which link back to more
regions. Following every link would import the whole catalogue from one root.

So references are followed one level deep. A root is at depth 0; an object it references is fetched
and imported at depth 1. The references of a depth 1 object are only **related** if the target
already exists, in the database or earlier in the same root. They are never fetched.

In the example of :ref:`developers-import-run`, the town is imported because the attraction
references it. The town's own reference to its managing organisation becomes a relation only if that
organisation was imported before, by an earlier item or job.

Media, keywords and accessibility specifications are exempt. They do not reference further objects
and cannot fan out, and capping them would silently strip records found through a relation.

Languages
=========

The languages of a job are the languages of the site. The default language becomes the record
itself; every other site language becomes a translation of it. A value upstream provides in a
language the site does not configure is ignored.

An entity reads each translatable field once per language, taking the value tagged with that
``@language``. Only fields that actually carry a translation end up in the translated record;
everything else falls back to the default language the usual TYPO3 way.

Inline children, such as addresses or opening hours, are translated only where their parent is. A
child translation without a translated parent has nowhere to hang and is dropped.

Values that disappear upstream
==============================

An entity hands over only the fields that carry a value; empty fields are left out of the row, and
the DataHandler keeps whatever was stored before. For a field the import does not manage this is
wanted: it is not overwritten with nothing.

A field the import does manage is declared in the entity's :php:`RECONCILED_FIELDS`. On a root of
that kind, a declared field upstream no longer supplies is cleared after the root is written, see
:ref:`developers-import-run-reconciliation`. On a kind that declares nothing, a value upstream
clears survives in the database.

Entities are shared between records
===================================

The parser keeps one instance per entity class and reuses it for every node of that kind. Before
each node, every property is reset to its declared default. A new property is covered automatically,
as long as it declares its default in the class. A property filled in the constructor or lazily on
first use would leak from one record into the next.