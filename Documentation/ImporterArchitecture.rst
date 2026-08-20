.. _importer-architecture:

=====================
Importer architecture
=====================

A short tour of how ``Classes/Import/`` is laid out and how a single import flows. Audience: TYPO3 extension developers picking up this codebase. The goal is to cover the moving parts and the boundaries between them, not every method.

Components
==========

- ``Importer`` — orchestrates one run. Picks a ``UrlProvider``, walks every URL, drives the DataHandler loop, returns the run's max log severity.
- ``UrlProvider`` (interface) — produces the list of root URLs for one configuration. Implementations: ``StaticUrlProvider`` (hand-listed URLs), ``SyncScopeUrlProvider`` (queries upstream for everything updated in a sync scope), ``ContainsPlaceUrlProvider``.
- ``Parser`` + ``Parser\Entity\*`` — pure transformation from a JSON-LD ``@graph`` to a ``DataHandlerPayload``. One ``Entity`` class per imported record type (``Organisation``, ``Town``, ``TouristInformation``, ``TouristAttraction``, ``ParkingFacility``); ``TransientEntity\*`` covers nested value objects (Address, OpeningHours, Offer, …). ``EntityInterface::handlesTypes()`` declares which ``@type`` strings a parser claims; priority breaks ties.
- ``DataHandlerPayload`` — typed bag the parser fills and the resolver/importer drain. Holds four buckets: ``dataMap``, ``cmdMap``, ``transients`` (unresolved JSON-LD references), ``translations`` (per-language partial rows). Plus auxiliary maps for default-language vs. translation rows.
- ``Resolver`` + ``ResolverContext`` — walks the parsed tree, resolves transients (looks up FKs by ``remote_id``, fetches missing nodes, re-parses them), drains translations once parents have uids. Visit-once contract is enforced via ``ResolverContext::remoteIdToKey``; the default-language and translation status maps short-circuit re-resolution across rounds.
- ``Importer\FetchData`` — HTTP boundary. Caches JSON-LD responses by ``(url, apiKey)`` SHA. Throws ``ResourceNotFoundException`` (subclass of ``InvalidResponseException``) for upstream 404 so callers can decide whether to drop, stub, or abort.
- ``ImportLogger`` — single producer of ``tx_thuecat_import_log`` + ``tx_thuecat_import_log_entry`` rows. Accumulates ``savingEntity`` rows from the payload plus DataHandler errorLog and caught exceptions, then flushes everything in one DataHandler call. Tracks max severity for the run.

Flow of one ``importConfiguration()`` call
==========================================

1. Resolve the ``UrlProvider`` and the site's ``defaultLanguage`` + ``translationLanguages`` map (from ``SiteFinder``).
2. Build a ``ResolverContext`` that owns the run's state (storagePid, language map, status maps, ``remoteIdToKey``).
3. **URL loop** — for each URL the provider returns:

   - Fetch JSON-LD. ``InvalidResponseException`` here → log as ``fetchingError`` and continue (run survives one broken root).
   - Run ``Parser::parse()``, then ``Resolver::resolve()``. Any ``Throwable`` from this branch → log as ``mappingError`` and continue.
   - Merge the resolved payload into a single ``accumulatedPayload``.

4. Snapshot the default-language datamap for the logger (translation rows are excluded so the savingEntity counts match what users see).
5. **Drain loop** — while ``dataMap`` or ``cmdMap`` is non-empty:

   - Fresh ``DataHandler`` per pass (state doesn't survive ``start()``).
   - ``process_datamap()`` then ``process_cmdmap()``.
   - Capture ``$dataHandler->errorLog`` into the import logger as ``dataHandlerError`` rows (severity ``error``).
   - Merge ``substNEWwithIDs``, promote NEW… placeholders in ``remoteIdToKey`` to real uids via ``ResolverContext::promoteNewKeys()``.
   - Re-run ``Resolver::resolve()`` against the now-empty payload — translations and post-localize fields land here.
   - Iteration cap: ``count($translationLanguages) * 2 + 2``. Round 0 writes defaults; each translation language needs one round to stage ``localize`` and one to fill the new translation row.

6. Flush the logger (savingEntity + recorded errors → one log row + N entries).
7. Return the run's max severity (``info`` for clean, ``error`` if anything raised).

Why the loop has multiple passes
================================

DataHandler's cmdMap collapses to ``[$table][$uid][$command] = $value`` — a second ``localize`` for the same parent uid silently overwrites the first. Each translation language therefore needs its own round: round N stages exactly one localize (which materializes the translation row), round N+1 picks up the new translation uid via ``promoteNewKeys`` and writes its translated fields via the translations bucket.

``ResolverContext::defaultStatus`` and ``translationStatus`` keep re-resolution idempotent across rounds — already-drained payloads short-circuit instead of re-fetching or re-querying.

Logging contract
================

- Every run produces one ``tx_thuecat_import_log`` row with N ``tx_thuecat_import_log_entry`` children.
- Entry types: ``savingEntity`` (one per default-language row inserted/updated), ``dataHandlerError`` (one per ``errorLog[]`` line DataHandler raised), ``mappingError`` / ``fetchingError`` (one per caught exception in the URL loop).
- Severity vocabulary is PSR-3 (``debug`` … ``emergency``). DataHandler errors and caught exceptions are recorded as ``error``; savingEntity rows are ``info``.
- Editors filter the BE list view by ``severity``. The ``Command::SUCCESS``/``FAILURE`` exit code is driven from ``ImportLogger::getMaxSeverity()`` — anything ``>= error`` fails the command.
- DataHandler's ``enableLogging`` stays ``true``. Setting it false would short-circuit ``errorLog`` too, so we accept the duplicate write to ``sys_log`` and let editors filter there.

Extending the importer
======================

- **New imported type**: add an ``Entity`` class in ``Parser/Entity/``, declare its TCA + DB columns, register the service-locator tag ``import.entity``. The parser picks it up via ``handlesTypes()``. If translatable, mirror an existing translatable TCA (e.g. ``tx_thuecat_organisation``).
- **New URL source**: implement ``UrlProvider``, register tag ``import.url.provider``, and add a matching ``type`` string to ``ImportConfiguration::getType()`` plus its FlexForm.
- **New transient field**: extend the relevant ``Entity::parse()`` to push refs into the payload's ``transients`` bucket; teach ``Resolver`` to drain the new key. This covers a **scalar** reference — one FK on the owner row. A property that produces a *set* of relations follows a different shape; see `Relation-set properties`_. A property whose references may land in *different tables* follows a third; see `One property, several target tables`_.
- **New log severity / type**: extend ``ImportLogger::SEVERITY_*``, the TCA select-list on ``tx_thuecat_import_log_entry.type``, and the ``xlf`` labels.

Relation-set properties
=======================

Some imported properties are not one value but a *set* of relations — media files, keywords,
``@type`` categories. Two of them (media, keywords) are built to the same shape, and the next one
should follow it rather than rediscover it. What follows is that shape and the reasons for each
part; the parts are load-bearing, not stylistic.

Several upstream shapes, one relation set
-----------------------------------------

Upstream rarely expresses such a property one way. ``schema:keywords`` arrives as an ``@id`` reference
to a vocabulary term, as a typed literal naming an ontology term by CURIE, or as free text an
editor typed. All three resolve to the same internal entry — identity, title, parent — and land in
one relation set. Detection belongs in a small reader class; the resolver should not branch on
shape.

Identity must be derived so that repeated imports reuse rather than accumulate. A URI is already
an identity; free text has none, so one is derived from the value (lowercased, ``mb_*`` throughout —
``strtolower()`` is byte-wise and splits ``Ölmühle`` from ``ölmühle`` into two records). Prefix the
identifier by source and shape so two shapes can never collide on one stored row.

The property is collected run-scoped, never handed to the payload
-----------------------------------------------------------------

Resolution **collects** entries onto ``ResolverContext``; it does not stage them into
``DataHandlerPayload``. A single flush after the last root writes them.

This is the part most easily got wrong. Two independent reasons:

- A relation field is submitted as the **complete** set, and the framework replaces what is stored
  with what is submitted. Anything missing from the submitted set is thereby removed. Staging
  during resolution submits an incomplete set, so entries resolved later in the run are wiped by
  the earlier write.
- Targets are shared heavily across roots — one vocabulary term is referenced by hundreds of
  objects. Resolution runs once per root URL, so a per-root write means one root removing what
  another had just written.

A collector is therefore run-scoped, guarded by a first-claim key of
``table|ownerKey|field|identity``. Owner and field belong in that key: the same target claimed by two
records must yield two relations, and only a repeat by the *same* owner collapses.

Removal falls out of submitting the complete set
------------------------------------------------

Because submission replaces, a target upstream no longer supplies loses its relation with no
deletion code — provided the submitted set is complete, which is what the deferred flush
guarantees. Only relations are removed; the shared target record itself stays, since editors may
still use it.

The corollary is the dangerous half: **an entry that failed to resolve is also missing from the
set, and would therefore be removed.** A technical failure is indistinguishable from an upstream
deletion, so the run records which owner/field had a failure and carries that owner's stored
targets forward into the submitted set. Only upstream positively reporting a target absent
(``404``, ``410``) may cost a relation — see ``FetchFailureVerdict``. Every other failure keeps what is
stored, because a credential, rate-limit or server fault arrives for every target on that host at
once and would otherwise strip a whole run.

One hole is known and shared by every property built this way: an owner that collects **nothing**
never enters the flush loop, so its relations survive even when upstream dropped all of them. It is
recorded in the project backlog and wants a pattern-level fix, not a per-property one.

Each property gets its own everything
-------------------------------------

Sharing an established path is the tempting shortcut and the wrong one. A new relation-set property
gets its **own** transient bucket, its own configuration anchor, its own collector, its own
run-scoped dedup map, and its own relation column. Where two properties both borrow
``sys_category``, that shared table is an implementation detail with no semantic meaning: they must
not share an anchor, a relation field, an identifier, or a dedup bucket.

Two properties sharing a dedup map hand each other staged keys, and the trees silently merge. Two
sharing an anchor put one property's records under the other's root, where a rootline-scoped lookup
then finds the wrong row.

Where a target's storage location varies by table, the owning entity declares it —
``EntityInterface::KEYWORD_FIELD`` and ``MEDIA_FIELDS`` are read from the entity, never assumed by the
resolver. The resolver sees only the payload, so such a declaration travels **in the bucket entry**
alongside the reference it belongs to.

Boundaries not to be modified
-----------------------------

- **The ``@type`` category path** — ``applyCategoryMapper()``, the ``_categories`` bucket,
  ``wireCategories()``. It looks like a general "category relations" mechanism and is not: it stamps
  the category anchor and dedups through the category map. A property placed there silently
  acquires both. A diff touching ``applyCategoryMapper()`` while adding a new property is the sign
  the boundary was crossed.
- **The payload's per-row transient harvest** — routing a relation set through it splits one
  record's set across two places at flush, which the completeness requirement above cannot work
  with.
- ``ResolverContext::promoteNewKeys()`` — it must learn every new run-scoped key map. A map left
  out is looked up by its ``NEW…`` placeholder in the next persistence round, misses, and stages a
  second row for a target that already exists. Silently: nothing errors.

Tripwires
---------

Each of these failures is invisible in ordinary testing, so each wants a test that fails when the
boundary is crossed:

- Two properties whose targets carry **deliberately identical titles**, asserting each tree holds
  exactly its own members and that same-titled rows are distinct records with distinct identifiers.
- **Two roots** referencing the same target, asserting one stored record and two relations — this
  is what catches dedup state living in a local instead of on the context.
- A re-import **dropping one** target of several, asserting the relation is gone and the target
  record remains.
- A **failed fetch** alongside a surviving entry, asserting nothing is removed. Note this needs a
  surviving entry: an owner whose every entry fails never reaches the flush, so the guard is inert
  and the test proves nothing.
- Ancestors or grouping records, if the property has them, asserting they are **not** related to
  the owner — only the target the record actually cites is a relation.

.. _import-contained-in-place:

One property, several target tables
===================================

``schema:containedInPlace`` is the case where a single upstream property points at records of
different kinds. Upstream uses it for whatever contains an object: the town it sits in, the
organisation responsible for it, or another place — a POI inside a park, a car park inside a
shopping centre.

A transient bucket normally names one target table and one relation field. This one cannot, so
``Resolver::BUCKET_MAP`` is keyed by table throughout: ``bucket => [table => field]``. Most
buckets hold a single entry; this one holds five.

.. list-table::
   :header-rows: 1

   * - Imported as
     - Relation
   * - Town
     - ``town``
   * - Organisation
     - ``contained_in_organisation``
   * - Tourist attraction
     - ``contained_in_attraction``
   * - Tourist information
     - ``contained_in_tourist_information``
   * - Parking facility
     - ``contained_in_parking_facility``

The field is chosen by the table the referenced record **actually imported into**
(``ResolverContext::remoteIdToTable``), not by the reference's ``@type``. The parser already
decided the table; re-deriving the kind from the type URI would give a second classifier for the
same question, free to drift from the first.

Why one field per table
-----------------------

"Any place" cannot be one relation. Extbase resolves a relation through a single concrete,
table-mapped class — its only polymorphism is a ``recordType`` column selecting a subclass *within
one table* (``DataMapper::getTargetType()``). A property typed across several tables produces a
query against a table named after the class and fails. The core's own multi-table group fixture
(``blog_example``'s ``tx_blogexample_domain_model_tag.items``) shows the same conclusion from the
other side: the owning record never maps that property, and every readable side is typed to one
concrete class.

So each target table gets its own field, and ``TouristAttraction::getContainedInPlaces()`` merges
them back into one list for templates. Adding a place table to the import means adding it to the
map **and** adding its field to the TCA of every owner table — the map is also the allowlist.

Every relation here is multi-value: a record can belong to more than one town (an airport serving
two cities) and to places of several kinds at once.

Probing and reporting
---------------------

Because a bucket may name several tables, the pre-fetch lookup probes them in map order and takes
the first hit. ``remote_id`` is unique per record, so one hit settles the question; the order only
decides how many queries run first. Commonest kind first.

A reference whose record imported into a table the bucket has no field for is logged as
``referenceUnrelatable`` at ``info``: the record exists and only the relation was dropped, which is
upstream data drift rather than a fault. A reference that produced **no record at all** — a type
this extension does not model — is not reported, because there was never a relation to lose. That
distinction is the point of the report; a change that makes it fire for everything, or for nothing,
has broken it.

Testing
=======

- Functional tests live in ``Tests/Functional/``, base class ``AbstractImportTestCase``. HTTP is staged via ``GuzzleClientFaker`` (file-keyed by URL); use ``expectFetch()`` / ``expectNotFound()`` / ``expectFetchForUrl()`` per scenario.
- Fixtures: payload data sets in ``Tests/Functional/Fixtures/Import/*.php``, JSON-LD response bodies under ``Tests/Functional/Fixtures/Import/Guzzle/<domain>/<path>/<id>.json``.
- Assertions: ``Tests/Functional/Assertions/Import/*.php`` define expected DB state after import.
- Run via ``ddev phpunit thuecat functional``. Single file: append a test path. Static analysis: ``ddev phpstan thuecat``. Style: ``ddev php-cs-fixer thuecat``.
