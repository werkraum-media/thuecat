.. _frontend-output-media:

=====
Media
=====

.. _frontend-output-media-import:

Import
======

Images and other files are imported into FAL and related to the record. The
target file directory is part of the import configuration.

An asset is downloaded once per run no matter how many records use it, and files
are named after the URL they were downloaded from, so a re-import reuses what is
already stored instead of fetching it again.

.. _frontend-output-media-ownership:

Which fields the import owns
----------------------------

``main_image`` and ``media_files`` mirror what upstream supplies. Each import
rebuilds them: an image upstream no longer lists has its relation removed.

**Images added to these fields by hand are removed by the next import.** The
relation carries no marker saying where it came from, so an editorial addition
cannot be told apart from a leftover of an earlier import.

Use ``editorial_images`` for images maintained in the backend. The import never
writes to that field and never removes anything from it, and its contents are
available in the frontend as ``editorialImages``.

.. note::

   One exception is worth knowing: a record for which upstream supplies **no**
   media at all is not cleaned up, so hand-added images on such a record survive.
   Do not rely on it — it follows from how the clean-up works, not from a
   decision to protect those images, and a record that later gains an image
   upstream loses them.

A download that fails does not remove the image it failed to fetch. Only a
``404`` or ``410`` from the media server counts as the asset being gone; a server
error, a refused or rate-limited request, or a run started with ``--no_media``
leave the stored relations alone.

.. _frontend-output-media-model:

FAL accessors
=============

A tourist attraction exposes its media as native Extbase FAL relations:

===================  =========================================================
Accessor             Meaning
===================  =========================================================
``mainImage``        The primary image (``main_image``), a single file
                     reference. Import-owned.
``mediaFiles``       Additional images and files (``media_files``).
                     Import-owned.
``editorialImages``  Editorially curated images (``editorial_images``),
                     maintained in the backend. Never touched by the import.
===================  =========================================================

``mainImage`` returns a single file reference or none; ``mediaFiles`` and
``editorialImages`` return a (possibly empty) collection.

.. _frontend-output-media-rendering:

Rendering
=========

The FAL relations are rendered with the standard Fluid image view helper, so
processing (cropping, scaling) and metadata (copyright, alternative text) are
available:

.. code-block:: html

   <f:if condition="{attraction.mainImage}">
       <figure>
           <f:image image="{attraction.mainImage}" />
           <f:if condition="{attraction.mainImage.originalResource.properties.copyright}">
               <figcaption>{attraction.mainImage.originalResource.properties.copyright -> f:format.htmlspecialchars()}</figcaption>
           </f:if>
       </figure>
   </f:if>

   <f:for each="{attraction.mediaFiles}" as="image">
       <f:image image="{image}" />
   </f:for>
