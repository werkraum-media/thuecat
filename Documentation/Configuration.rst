.. include:: Includes.txt
.. _configuration:

=============
Configuration
=============

.. _api-key:

API Key
-------

Some API requests are only possible by providing an API Key.
This key can be configured via "Extension Configuration".

.. _storage-pids:

Storage Pids
------------

The configuration of imports is stored within TYPO3 records.
Those records can be created via backend module.
Each new record is stored on a default page. The default is ``0``.
That would prevent editors from creating and editing records.
This page uid can be configured via TypoScript::

   module {
       tx_thuecat {
           settings {
               newRecordPid {
                   tx_thuecat_import_configuration = 10
               }
           }
       }
   }

.. _import-configuration:

Import configuration
--------------------

Each import is defined via a special import configuration record.
This record can be created via TYPO3 backend module.

There are different configurations available:

Static list of URLs
   Allows to define a list of URLs which should be imported.
   These URLs should reference a single resource to import without any given parameters like a format.

Synchronization area
   Allows to import a so called "Synchronisationsbereich".
   Find out more at https://cms.thuecat.org/developer.
   Add the given ``syncScopeId`` to the configuration to update the given resources
   for that specific sync scope.
   This requires an configured :ref:`api-key`.

All configurations also provide an input to define the page where records should be
stored and updated.
