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

Files go into FAL, into the folder the import configuration names. Every run first creates a staging
folder below it and downloads into that.

* At the end of a run that completes, staged files are moved into the target folder. A file of the
  same name already there wins; the staged copy is dropped.
* The staging folder is discarded at the end of every run, whether it completed or not. A failed run
  leaves no stray files.

A file is downloaded once per run, however many records use it. Its name is derived from the URL
upstream supplied — a readable stem and a hash of the URL — so the next run finds it again instead
of downloading it twice. Redirects are followed up to five hops; the name stays the one of the
original URL, so an asset remains one file across runs. A redirect without a target, or a longer
chain, counts as a failed download.

A run with :shell:`--no_media` downloads nothing and needs no writable folder. It does not touch
stored image relations either.

Re-imports and removal
======================

A re-import reuses the stored file reference of an image instead of adding a second one.

An image upstream no longer supplies loses its file reference; the file stays in the folder.
Only ``404`` or ``410`` from the media server count as an image being gone. A server error, a
refused or rate-limited request or a failed download leaves stored relations alone.

The gap shared by all sets applies: a record for which upstream supplies **no** media at all is not
cleaned up. Its relations survive, including images an editor added by hand. Nothing should rely on
that; once upstream supplies an image for that record, the manually added ones are removed.