.. include:: ../../Includes.txt
.. _developers-import-log:

==============
The import log
==============

Every run that gets past the configuration check writes one :sql:`tx_thuecat_import_log` record with
its entries in :sql:`tx_thuecat_import_log_entry`. It is what editors see in the backend module
(:guilabel:`ThueCat -> Imports`) and what a developer reads first when a run behaves unexpectedly.

.. contents:: On this page
   :local:
   :depth: 1

Severity
========

Every entry has a PSR-3 severity, ``debug`` to ``emergency``. The run returns the highest severity
it recorded, and the command turns that into its exit code: ``error`` or worse fails the command.
See :ref:`cli-exit-codes`.

Choose the severity by what the reader has to do:

* ``error`` — something that should have been written was not.
* ``warning`` — data was dropped or kept against expectation; a person should look.
* ``notice`` — worth knowing, nothing lost.
* ``info`` — the record of what happened.
* ``debug`` — diagnostics.

The backend module lists errors, or the warnings where there are no errors; warnings next to errors
would only bury them. Notices and warnings are additionally shown grouped by type.

Entry types
===========

.. list-table::
   :header-rows: 1

   * - Type
     - Severity
     - Written when
   * - ``effectiveSettings``
     - debug
     - Once per run, before fetching: the settings in effect.
   * - ``savingEntity``
     - info
     - Per record of the default language written.
   * - ``fetchingError``
     - error
     - A root could not be fetched.
   * - ``mappingError``
     - error
     - A root could not be parsed or resolved.
   * - ``dataHandlerError``
     - error
     - The DataHandler reported an error.
   * - ``runAborted``
     - error
     - The run budget was used up.
   * - ``runFailed``
     - error
     - An unexpected exception ended the run.
   * - ``retriesRecovered``
     - notice
     - Requests succeeded only after retries, see :ref:`recovered-retries`.
   * - ``referenceSkipped``
     - warning
     - A reference could not be fetched, see :ref:`developers-import-references`.
   * - ``referenceUnrelatable``
     - info
     - A reference led to a table its property has no field for.
   * - ``categoryMatched``, ``categoryUnmatched``
     - info
     - Which ``@type`` values the category mapping knew.
   * - ``categoryParentChosen``
     - debug
     - A category had several possible parents; which one was taken.
   * - ``categoryParentUnpreferred``
     - warning
     - No preferred parent fitted.
   * - ``categoryWithoutHierarchy``
     - notice
     - A type the vocabulary does not know.
   * - ``vocabularyStale``, ``vocabularyUnavailable``
     - warning
     - The vocabulary could not be refreshed, or not loaded at all.
   * - ``categoriesFieldMissing``
     - notice
     - Values for a field the table does not have.
   * - ``scheduleDaySkipped``, ``scheduleDayDropped``
     - warning
     - Event schedule days that could not be used.
   * - ``eventWithoutDates``, ``eventDateSkipped``
     - warning
     - Events that ended up without usable dates.
   * - ``eventPlaceMatch``
     - info
     - An event matched, or failed to match, a place.

What the log does not cover:

* A configuration rejected before the run writes no log; the command prints the reason.
* The DataHandler keeps its own logging, so a run also writes to :sql:`sys_log`, for every record it
  writes. It cannot be switched off: the DataHandler collects its errors only while it logs, and
  those errors are what ``dataHandlerError`` reports.

Adding an entry type
====================

An entry is written by a method on :php:`WerkraumMedia\ThueCat\Import\ImportLogger`. For the entry
to be visible anywhere, the type has to be registered in all of these places:

#. An item in the ``type`` select of the TCA of :sql:`tx_thuecat_import_log_entry`, with its label
   in :file:`locallang_tca.xlf`.
#. An entry in the TCA ``types`` of that table. Without it, the record falls back to another type's
   fields and hides ``message`` and ``context`` — the fields that carry the content.
#. A model class below :php:`WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLogEntry`.
#. That class in :file:`Configuration/Extbase/Persistence/Classes.php`, as a subclass of
   :php:`ImportLogEntry` and with its ``recordType``. The base class is abstract; without the
   mapping, the row cannot be loaded at all.

The backend module does not list entries generically. It renders accessors on
:php:`WerkraumMedia\ThueCat\Domain\Model\Backend\ImportLog`. Errors, warnings and notices appear
through the existing ones; an ``info`` or ``debug`` entry needs an accessor and a place in the
template to be shown.