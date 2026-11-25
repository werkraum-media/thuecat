.. include:: ../../Includes.txt
.. _developers-import-media:
.. _import-media-files:

===========
Media files
===========

Images are a set of shared targets and follow the shape described in
:ref:`developers-import-relation-sets`. This page covers what is specific to them: how a file gets
onto disk and the relation into the right field.

.. contents:: On this page
   :local:
   :depth: 1

Where an image goes
===================

Upstream offers images in several properties. The entity records each with the property it came
from, and declares per property which field of its table receives it:

.. code-block:: text

   TouristAttractionEntity::MEDIA_FIELDS
       photo → main_image
       image → media_files

A trail adds ``logo``; an event puts both into ``images``. An entity without :php:`MEDIA_FIELDS`
stores no media. Videos are not downloaded.

An image arrives either as a reference to a media object, which is fetched to learn the file URL, or
inline with its data in place. Inline images need no fetch and are handled as soon as the owner row
exists. Both end up in the same collector.

Download, staging and promotion
===============================

Files go into FAL, into the folder the import configuration names. Every item of a job first
creates a staging folder of its own below it, named after the job and the item, and downloads into
that.

* When the item ends, staged files are moved into the target folder, whether the item completed or
  not: a file in the staging folder was downloaded completely. A file of the same name already
  there wins; the staged copy is dropped. The staging folder is then removed.
* An item killed before it ends leaves its staging folder behind. The end of its job removes it, and
  the supervising command removes the folders of jobs no longer open.

A file is downloaded once per job, however many records use it: a later item finds the file an
earlier one promoted. Its name is derived from the URL upstream supplied — a readable stem and a
hash of the URL — so the next job finds it again instead of downloading it twice. Redirects are
followed up to five hops; the name stays the one of the original URL, so an asset remains one file
across jobs. A redirect without a target, or a longer chain, counts as a failed download.

A job that skips media downloads nothing and needs no writable folder. It does not touch stored
image relations either.

Re-imports and removal
======================

A re-import reuses the stored file reference of an image instead of adding a second one.

An image upstream no longer supplies loses its file reference; the file stays in the folder.
Only ``404`` or ``410`` count as an image being gone, from the media object as from the download. A
server error, a refused or rate-limited request or a failed download leaves the stored references
of that field alone.

A root of a :ref:`reconciled kind <developers-import-run-reconciliation>` for which upstream
supplies **no** media at all loses its file references too, editors' ones included, since a file
reference carries no ``remote_id`` to tell them apart. On other kinds, and on records reached only
through a reference, such a record keeps its references.

The author of an image is read from the node when upstream supplies it inline with a blank node
identifier, and fetched only when it is a URL. An author that cannot be fetched leaves the image
without an author.