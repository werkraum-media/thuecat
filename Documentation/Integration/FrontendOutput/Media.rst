.. include:: ../../Includes.txt
.. _frontend-output-media:

=====
Media
=====

Images and other files are imported into FAL and related to the record, see :ref:`import`.

.. _frontend-output-media-ownership:

Which fields the import owns
============================

:sql:`main_image` and :sql:`media_files` mirror what upstream supplies. Each import rebuilds them:
an image upstream no longer lists has its relation removed.

**Images added to these fields by hand are removed by the next import.** The relation carries no
marker saying where it came from, so an editorial addition cannot be told apart from a leftover of
an earlier import.

Use :sql:`editorial_images` for images maintained in the backend. The import never writes to that
field and never removes anything from it, and its contents are available in the frontend as
``editorialImages``.

.. _frontend-output-media-model:

FAL accessors
=============

A tourist attraction exposes its media as native Extbase FAL relations:

=================== ========================================================= Accessor Meaning
=================== ========================================================= ``mainImage`` The
primary image (:sql:`main_image`), a single file
                     reference. Import-owned.
``mediaFiles``       Additional images and files (:sql:`media_files`).
                     Import-owned.
``editorialImages``  Editorially curated images (:sql:`editorial_images`),
                     maintained in the backend. Never touched by the import.
===================  =========================================================

A trail exposes its media the same way:

=================== ========================================================= Accessor Meaning
=================== ========================================================= ``mainImage`` The
primary image (:sql:`main_image`), a single file
                     reference. Import-owned.
``mediaFiles``       Additional images and files (:sql:`media_files`).
                     Import-owned.
``logo``             The trail's logo (``logo``), a single file reference.
                     Import-owned.
===================  =========================================================

Trails have no field for editorially maintained images: every trail media field is rebuilt by the
import.

``mainImage`` and ``logo`` return a single file reference or none;
``mediaFiles`` and ``editorialImages`` return a (possibly empty) collection.

.. _frontend-output-media-rendering:

Rendering
=========

The FAL relations are rendered with the standard Fluid image view helper, so processing (cropping,
scaling) and metadata (copyright, alternative text) are available:

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
       <f:image image="{image}" cropVariant="default"/>
   </f:for>
