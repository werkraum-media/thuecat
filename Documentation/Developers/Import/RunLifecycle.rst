.. include:: ../../Includes.txt
.. _developers-import-run:
.. _importer-architecture:

==============
How a job runs
==============

An import runs as a job: a chain of messages on the ``doctrine`` transport of the message bus, one
per root. Each root passes through the same phases every time. Knowing them tells you where a change
belongs and what it can rely on: a value written during parsing cannot know a database uid yet, a
relation flushed before the root is resolved cannot be complete.

How integrators start, schedule and stop jobs is described in :ref:`import`.

.. contents:: On this page
   :local:
   :depth: 1

.. _developers-import-run-job:

The job
=======

A job is a row of :sql:`tx_thuecat_import_job`, its work list rows of
:sql:`tx_thuecat_import_job_item`. Neither table has TCA. The messages, handled one after the other
by the message worker:

``ImportStarted``
   Validates the configuration, reports the effective settings, checks the file folder and asks
   the configuration's URL provider for the roots, see :ref:`developers-import-sources`. It stores
   one item per root to import and, for a sync channel, one item per ThueCat ID to delete, then
   sends the message of the first item. A configuration that fails here fails the job at once.

``ImportRoot``
   Imports one root, see :ref:`developers-import-run-root`.

``DeleteRoot``
   Deletes the records of one ThueCat ID in the site, for a sync channel.

``ImportFinished``
   Decides the job's status, records :sql:`last_run` and :sql:`last_successful_run` on the
   configuration, schedules the next start and removes media staging folders the job left behind.

``ImportDue``
   Sent with a delay to the next scheduled start; starts a job when the configuration is still due
   then.

An item's handler sends the message of the next item before it imports its own, so a failing item
never breaks the chain. The handlers guard against a message that arrives twice: an item that is no
longer pending is not imported again.

A message that fails is sent again a minute later, until its item had three attempts; then the item
is failed and the chain goes on. Each message has its own time limit, the message budget: it is
checked before every fetch and before every round of writing, never in the middle of a write.

The supervising command ``thuecat:import:supervise`` repairs what the chain cannot: it starts due
configurations, resends the message of a job whose last progress is older than twice its message
budget and that has no message waiting, resends lost starts, and removes staging folders nothing
uses any more.

.. _developers-import-run-root:

An example root
===============

An import configuration of type ``static`` lists one URL, a tourist attraction. Its JSON-LD says the
attraction lies in a town (``schema:containedInPlace``) and is managed by an organisation
(``thuecat:managedBy``). The site has German as its default language and English as a translation.

#. **Prepare.** The languages come from the site that owns the storage page: ``de`` becomes the
   default row, ``en`` a translation. A staging folder of this item is created below the file
   folder for downloaded media.
#. **Fetch and parse the root.** The attraction is fetched. The parser picks the entity that claims
   the node's ``@type`` and turns it into a row with German values, English translations and two
   open references: the town and the organisation.
#. **Resolve.** Neither the town nor the organisation is known yet, so both are fetched, parsed and
   added to the payload as rows of their own. Had an earlier item stored them, they would be found
   in the database instead. Their own references are not followed any further, see
   :ref:`developers-import-fetch-depth`.
#. **Flush what needs the whole root.** Media and keywords are written only once the root is
   resolved, because their relations must be complete, see :ref:`developers-import-relation-sets`.
#. **Remove what upstream no longer supplies** from inline children and translations of the root,
   in a DataHandler pass of its own, see :ref:`developers-import-run-reconciliation`.
#. **Persist in rounds.** The DataHandler writes the rows, then the translations, then the relations
   that could only be wired once uids existed.
#. **Match events to places**, if the root is an event, see
   :ref:`developers-import-event-place-matching`.
#. **Clear the fields upstream no longer supplies** on the root, in the last DataHandler pass.
#. **Promote media.** Staged files are moved into the file folder, and the staging folder is
   removed, however the item ends.

Every root starts on a fresh resolver context. Nothing of an earlier root is kept in memory; what it
stored is found in the database.

.. _developers-import-run-rounds:

Why persisting takes several rounds
===================================

The DataHandler cannot do everything in one call:

* A new record has a ``NEW…`` placeholder until it is written. Relations can use that placeholder,
  but for example translations can only be staged once the placeholder has become a uid.
* A translation is created with the ``localize`` command. Commands are keyed by table, record and
  command name, so two ``localize`` commands for the same record overwrite each other. Each
  translation language therefore needs a round of its own to create the translated record, and
  another to fill in its fields.
* Categories created in one round can only be localized in the next.

The number of rounds is bounded by the number of site languages: two per translation language plus
three for the default language. A root that is still not done after that throws an exception instead
of looping on. If this happens, the root staged something that never settles; the exception message
lists what was left.

Every round gets a fresh DataHandler instance. The DataHandler keeps state between calls, and mixing
that state across rounds produces wrong relations.

.. _developers-import-run-reconciliation:

Reconciliation
==============

A root's entity declares in :php:`RECONCILED_FIELDS` which fields the import owns, and how a value
upstream no longer supplies is removed, as a
:php:`WerkraumMedia\ThueCat\Import\Reconciliation\FieldMechanism`:

``Scalar``
   The column is written empty for its type.

``ReferenceWrite``
   The column pointing at another record is written empty; that record stays.

``RelationReplace``
   A relation list is submitted as the supplied set, plus every related record the import did not
   create, such as a category an editor added.

``ExplicitDelete``
   Stored inline children or file references that are not supplied are deleted.

A field counts as supplied when the entity wrote it, when the resolver set it, or when the resolver
claimed it because it could not tell: a reference that failed to fetch, a media download that
failed, a job that skips media, or a category kind without anchor. Each removal is logged as
``removedUpstream``. The integrator's view is :ref:`import-reconciliation`.

An entity that declares nothing is not reconciled. A kind declares its own fields, never inherits
another table's.

.. _developers-import-run-failures:

When something fails
====================

The import is built to keep going where one object is broken and to stop where the whole item is in
doubt.

One root cannot be fetched
   Logged as ``fetchingError``; the item ends and the job goes on with the next one.

One root cannot be parsed or resolved
   Logged as ``mappingError``; the item ends and the job goes on with the next one.

One reference cannot be fetched
   Logged as ``referenceSkipped``; the owning record is written without that relation. See
   :ref:`developers-import-relation-failures` for what happens to relations stored earlier.

The message budget is used up
   Logged as ``runAborted``. The message fails and is retried, see above.

Anything else
   An unexpected exception fails the message. It is logged as ``itemFailed`` with the phase reached,
   and retried.

A configuration rejected by the start writes ``runFailed`` and fails the job before any item.
