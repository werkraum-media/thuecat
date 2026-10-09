.. include:: ../../Includes.txt
.. _developers-import-run:
.. _importer-architecture:

===============
How a run works
===============

A run passes through the same phases every time. Knowing them tells you where a change belongs and
what it can rely on: a value written during parsing cannot know a database uid yet, a relation
flushed before the last root cannot be complete.

.. contents:: On this page
   :local:
   :depth: 1

An example run
==============

An import configuration of type ``static`` lists one URL, a tourist attraction. Its JSON-LD says the
attraction lies in a town (``schema:containedInPlace``) and is managed by an organisation
(``thuecat:managedBy``). The site has German as its default language and English as a translation.

#. **Check the configuration.** Storage page, category anchors and file folder are validated before
   anything is fetched. A broken configuration stops here.
#. **Prepare.** The languages come from the site that owns the storage page: ``de`` becomes the
   default row, ``en`` a translation. A per-run staging folder is created below the file folder for
   downloaded media.
#. **Fetch and parse the root.** The attraction is fetched. The parser picks the entity that claims
   the node's ``@type`` and turns it into a row with German values, English translations and two
   open references: the town and the organisation.
#. **Resolve.** Neither the town nor the organisation is known yet, so both are fetched, parsed and
   added to the payload as rows of their own. Their own references are not followed any further, see
   :ref:`developers-import-fetch-depth`.
#. **Flush what needs the whole run.** Media and keywords are written only after the last root,
   because their relations must be complete, see :ref:`developers-import-relation-sets`.
#. **Persist in rounds.** The DataHandler writes the rows, then the translations, then the relations
   that could only be wired once uids existed.
#. **Match events to places**, if the run imported events, see
   :ref:`developers-import-event-place-matching`.
#. **Write the log and promote media.** The import log is written, staged files are moved into the
   file folder, and the run returns with its highest severity.

With more roots, steps 3 and 4 repeat per root before step 5 runs once.

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
three for the default language. A run that is still not done after that throws an exception instead
of looping on. If this happens, the run staged something that never settles; the exception message
lists what was left.

Every round gets a fresh DataHandler instance. The DataHandler keeps state between calls, and mixing
that state across rounds produces wrong relations.

When something fails
====================

The import is built to keep going where one object is broken and to stop where the whole run is in
doubt.

One root cannot be fetched
   Logged as ``fetchingError``; the run continues with the next root.

One root cannot be parsed or resolved
   Logged as ``mappingError``; the run continues with the next root.

One reference cannot be fetched
   Logged as ``referenceSkipped``; the owning record is written without that relation. See
   :ref:`developers-import-relation-failures` for what happens to relations stored earlier.

The run budget is used up
   Checked before each root and before each round, never in the middle of a write. What was
   collected so far is still written, and the log records ``runAborted``.

Anything else
   An unexpected exception ends the run. The log records ``runFailed`` with the phase it happened
   in.

No matter the reason, after the run the staging folder for downloaded files is discarded, and any
exception is rethrown.

A configuration rejected in the first step writes no import log. The command prints the reason.