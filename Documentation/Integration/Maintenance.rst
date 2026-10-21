.. include:: ../Includes.txt
.. _maintenance:

===========
Maintenance
===========

.. contents:: On this page
   :local:
   :depth: 1

.. _maintenance-slugs:

Slugs
=====

Tourist attractions, trails, towns and categories carry a slug: the URL segment of detail pages and
of list filters, see :ref:`frontend-output-routing`. Records created in the backend or by an import
get their slug from their title. A slug never changes afterwards, also not when the title changes
upstream, so URLs stay stable.

Records without a slug, for example those that existed before the slug columns, get one from the
:ref:`upgrade wizard <t3coreapi:upgrade-wizards>` :guilabel:`ThueCat: Fill empty slugs of detail
and filter records`:

#. Update the database schema, so the slug columns exist:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the wizard, in the Install Tool under :guilabel:`Upgrade > Upgrade Wizard`, or on the
   console:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run thuecat_detailRecordSlugs

#. Flush the caches. The wizard writes the slugs directly, without the DataHandler, so no cache is
   flushed per record:

   .. code-block:: bash

      vendor/bin/typo3 cache:flush

The wizard fills every empty slug, per record and language, and leaves existing slugs untouched. It
covers every detail record kind, including those other packages register, see
:ref:`developers-detail-record-kinds`.

The wizard is repeatable: it is offered again whenever a record without a slug exists, for example
after a release that adds a slug column to another table.

.. _maintenance-event-relations:

Events of places
================

Tourist attractions, organisations, towns and tourist information list the events the import
matched to them, see :ref:`developers-import-event-place-matching`. These relations are stored in
MM tables. Relations an older version stored as comma-separated lists are moved there by the
:ref:`upgrade wizard <t3coreapi:upgrade-wizards>` :guilabel:`ThueCat: Move event relations of
places into MM tables`:

#. Update the database schema, so the MM tables exist:

   .. code-block:: bash

      vendor/bin/typo3 extension:setup

#. Run the wizard, in the Install Tool under :guilabel:`Upgrade > Upgrade Wizard`, or on the
   console:

   .. code-block:: bash

      vendor/bin/typo3 upgrade:run thuecat_eventRelationsToMm

The wizard keeps the order of each list and covers translations and deleted records too. It
relates the same events as before, so no cache needs flushing.

The wizard is repeatable: it is offered again while a stored list remains, and running it twice
creates no duplicate relation.
