.. include:: ../../Includes.txt
.. _developers-import:

==========
The import
==========

This chapter is for developers who extend the import: a new record kind, a new property, a new
relation, or a new way to select what gets imported. It explains the concepts the import is built on
and the rules a change has to keep. For which class does what, read the code; the pages here explain
why it is shaped the way it is.

How to configure and run an import is described for integrators in :ref:`import`.

Terms used throughout
=====================

Job
   One execution of one import configuration record, from its start to its status. A job imports
   its roots one item at a time, see :ref:`developers-import-run`.

Root
   An upstream object the import configuration asks for directly, by URL. A job has one item per
   root.

Entity
   A parser for one kind of upstream object. It turns one JSON-LD node into one database row of one
   table.

Reference
   A relation the upstream data expresses by ``@id`` only, such as the town an attraction lies in.
   The entity cannot resolve it on its own and leaves it for the resolver.

Payload
   Everything a root has parsed and is about to write: rows, translations, open references, commands.
   It is handed to the TYPO3 DataHandler at the end.

Round
   One pass of the DataHandler over the payload. A root needs several, see
   :ref:`developers-import-run-rounds`.

.. toctree::
   :maxdepth: 1
   :titlesonly:

   RunLifecycle
   RecordIdentity
   AddingARecordKind
   Relations
   CategoryTrees
   Media
   ImportLog
   ImportSources
   Tuning
   Testing