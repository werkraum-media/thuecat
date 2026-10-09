.. include:: ../../Includes.txt
.. _developers-import-relations:

=========
Relations
=========

The import knows four shapes of relation. Each has its own mechanism, and a new property should take
the one that fits.

.. list-table::
   :header-rows: 1

   * - Shape
     - Example
     - Mechanism
   * - Reference to another upstream object
     - the town an attraction lies in
     - :ref:`developers-import-references`
   * - Child record built from nested data
     - an address, opening hours, a trail's start location
     - :ref:`developers-import-inline-children`
   * - Set of shared targets
     - media files, keywords, categories derived from ``@type``
     - :ref:`developers-import-relation-sets`
   * - Event matched to a place
     - the venue an event takes place at
     - :ref:`developers-import-event-place-matching`

.. contents:: On this page
   :local:
   :depth: 1

.. _developers-import-references:

References to other upstream objects
====================================

A reference is a property whose value is an ``@id``: ``"thuecat:managedBy": {"@id":
"https://thuecat.org/resources/…"}``. The entity cannot know what lies behind the ``@id``, so it
records the value with :php:`recordTransient()` under the property name without its prefix, here
``managedBy``. That name is the reference's **bucket**.

The resolver looks each reference up and writes the relation:

#. Already imported in this run? Relate to it.
#. Stored in the site? Relate to it, and fetch it again to update it — unless the owner was itself
   only reached as a relation, see :ref:`developers-import-fetch-depth`.
#. Neither? Fetch, parse and import it, then relate to it.

Which field the relation goes into is decided by :php:`Resolver::BUCKET_MAP`: per bucket, per target
table, one field on the owner. A bucket the map does not know stops the run with an exception; this
is a programming error, not a data problem.

.. code-block:: text

   managedBy             tx_thuecat_organisation        → managed_by
   parkingFacilityNearBy tx_thuecat_parking_facility    → parking_facility_near_by

Adding a reference property therefore takes three steps: record it in the entity, add the bucket to
the map, add the field to the owner's TCA.

What the log says about references
----------------------------------

``referenceSkipped`` (warning)
   The target could not be fetched or parsed. The owner is written without the relation. A URL that
   failed once is not requested again in the same run.

``referenceUnrelatable`` (info)
   The target was imported, but into a table the bucket has no field for. The record exists;
   only the relation was dropped. This is upstream data the map does not anticipate.

Nothing
   The target's ``@type`` is not one any entity claims. No record was created, so no relation was
   lost.

Keep that distinction when changing the resolver. A report that fires for everything, or for
nothing, has lost its meaning.

.. _import-contained-in-place:
.. _developers-import-contained-in-place:

One property, several target tables
-----------------------------------

``schema:containedInPlace`` names whatever contains an object: the town it lies in, the organisation
responsible for it, or another place — a sight inside a park, a car park inside a shopping centre.
Its bucket therefore maps several tables:

.. list-table::
   :header-rows: 1

   * - Target imported as
     - Field on the owner
   * - Town
     - ``town``
   * - Organisation
     - :sql:`contained_in_organisation`
   * - Tourist attraction
     - :sql:`contained_in_attraction`
   * - Tourist information
     - :sql:`contained_in_tourist_information`
   * - Parking facility
     - :sql:`contained_in_parking_facility`
   * - Trail
     - :sql:`contained_in_trail`

The field is chosen by the table the target **was imported into**, not by its ``@type``. The parser
already decided the table; deciding again from the type would be a second classifier for the same
question, free to disagree with the first.

Why not one field for all of them? Extbase resolves a relation through one concrete class per table.
A property typed across several tables cannot be mapped. So every target table gets its own field,
and the frontend model merges them back into one list
(:php:`TouristAttraction::getContainedInPlaces()`).

The order of the map matters for one thing: a stored target is looked up table by table, in that
order, until one matches. Put the most frequent kind first.

.. _developers-import-inline-children:

Child records built from nested data
====================================

An address, opening hours or the start location of a trail arrive nested inside their owner, without
an ``@id`` of their own. The owning entity builds them as child entities, and they are stored as
inline (IRRE) records of the owner.

Each child's :sql:`remote_id` is the owner's, a separator and a position, for example
``…/123::addr::0``. The resolver uses the separator to find the owner and lists the child in the
owner's inline field. A child table may spread over several fields by a value of its own: opening
hours go into :sql:`opening_hours_inline` or :sql:`special_opening_hours_inline` by their
specification type.

The mapping lives in :php:`Resolver::INLINE_CHILD_PARENTS`. A table marked there to reap orphans
loses stored children upstream no longer supplies; addresses and the parts of a trail are, opening
hours are not yet.

Event dates are children too, but point back at their event through a reference in the ``event``
bucket instead of being listed by it.

.. _developers-import-relation-sets:

Sets of shared targets
======================

Some properties are a set of relations to records many owners share: media files, keywords, the
categories derived from ``@type``. These follow one shape, and a new such property can just plug
into the mechanics.

Several upstream shapes, one set
--------------------------------

Upstream rarely expresses such a property one way. ``schema:keywords`` arrives as an ``@id`` of a
vocabulary term, as a typed literal naming an ontology term, or as free text an editor typed. All
three end up as the same kind of entry — identity, title, parent — in one set. A small reader class
tells the shapes apart; the resolver does not branch on them.

The identity must be stable across runs, so that a re-import reuses rather than adds. A URI is an
identity already. Free text has none, so one is derived from the value: lowercased with the
:php:`mb_*` functions — :php:`strtolower()` works on bytes and would turn ``Ölmühle`` and
``ölmühle`` into two records — and prefixed by its shape, ``keyword:text:ölmühle``, so two shapes
never collide.

Collect during the run, write once at the end
---------------------------------------------

Resolution **collects** entries on the run's context instead of putting them into the payload. One
flush after the last root writes them. There are two reasons for the delay:

* A relation field is submitted as the complete set; what is not submitted is removed. Writing
  during resolution submits an incomplete set, and entries resolved later are wiped by the earlier
  write.
* Targets are shared. One keyword is referenced by several objects across many roots. A write per
  root means one root removing what another just wrote.

Collected entries are deduplicated by owner, field and target. The same target claimed by two owners
gives two relations; only a repeat by the same owner collapses.

.. _developers-import-relation-failures:

Removal, and when not to remove
-------------------------------

Because a submitted set is complete, a target upstream no longer supplies loses its relation without
any deletion code. Only the relation goes; the shared target stays, editors may still use it.

The dangerous half: **an entry that failed to resolve is missing from the set too.** A technical
failure looks exactly like an upstream deletion. So the run remembers which owner and field had a
failure and submits that owner's stored relations unchanged. Only upstream positively saying a
target is gone — HTTP ``404`` or ``410`` — may remove that relation. A server error, a refused
credential or a rate limit hits every target on that host at once and would otherwise strip a whole
run.

Relations an editor added by hand, to a category without a :sql:`remote_id`, are kept as well.

Media works the same way, with one difference: file relations add rather than replace, so stale file
references are deleted explicitly instead of left out.

.. note::

   A known gap shared by every property of this shape: an owner for which upstream supplies **no**
   entry at all is never part of the flush, so its relations survive even when upstream dropped all
   of them.

Each property gets its own everything
-------------------------------------

Sharing an existing path is the tempting shortcut and the wrong one. A new property of this shape
gets its **own** reference bucket, its own anchor settings, its own collector, its own deduplication
and its own relation column.

That two properties both store their values as :sql:`sys_category` records means nothing. Two
properties sharing a deduplication map hand each other keys, and their trees merge. Two sharing an
anchor put one property's records under the other's root. Keywords and ``@type`` categories are
separate for exactly this reason, see :ref:`developers-import-categories`.

Do not route a new property through the ``@type`` category path — the :php:`_categories` list filled
by :php:`applyCategoryMapper()`. It looks like a general mechanism for category relations and is
not: whatever goes through it gets the ``@type`` anchor and deduplication.

Where an owner table keeps such values differs per table, so the entity declares it
(:php:`KEYWORD_FIELD`, :php:`MEDIA_FIELDS`) and the declaration travels with each entry. The
resolver only sees the payload, never the entity.

Every map of keys that has to survive between rounds must be known to
:php:`ResolverContext::promoteNewKeys()`. A map it does not update still holds ``NEW…`` placeholders
in the next round, misses, and creates a second record for a target that exists. Nothing reports an
error.

Tests a new property of this shape needs
----------------------------------------

Each of these failures is invisible in ordinary testing:

* Two properties whose targets have **identical titles** — each tree holds only its own members.
* **Two roots** referencing the same target — one stored record, two relations. This catches
  deduplication state living in a local variable instead of on the context.
* A re-import **dropping one** of several targets — the relation is gone, the target record stays.
* A **failed fetch** next to an entry that resolves — nothing is removed. The entry that resolves
  matters: an owner whose every entry fails never reaches the flush, and the test proves nothing.
* Grouping records above a target, if the property has them — they are **not** related to the owner.

.. _developers-import-event-place-matching:

Events matched to places
========================

An event names its venue in ``schema:location`` and its organizer in ``schema:organizer``. Where
these point at a place that is imported as a record of its own, the place gets a relation to the
event: :sql:`hosts_events` for the venue on any kind of place, :sql:`manages_event` for the
organizer, on organisations only.

The place is found among the records already stored in the site, never fetched:

* by :sql:`remote_id`, where the event gives an ``@id``;
* otherwise by name and postal code, where exactly one place matches.

The relation is written after all other rounds, because only then do new events have uids. Every
attempt is logged as ``eventPlaceMatch`` with its outcome, so an editor can see why an event did or
did not end up at a place.