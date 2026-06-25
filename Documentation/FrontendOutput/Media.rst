.. _frontend-output-media:

=====
Media
=====

.. _frontend-output-media-import:

Import
======

Images and other files are imported into FAL and related to the record. The
target file directory is part of the import configuration.

.. _frontend-output-media-model:

FAL accessors
=============

A tourist attraction exposes its media as native Extbase FAL relations:

==================  ==========================================================
Accessor            Meaning
==================  ==========================================================
``mainImage``       The primary image (``main_image``), a single file reference.
``mediaFiles``      Additional images and files (``media_files``).
``editorialImages``  Editorially curated images (``editorial_images``), maintained in the backend.
==================  ==========================================================

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
