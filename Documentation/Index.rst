.. include:: Includes.txt
.. _start:

===========
EXT:thuecat
===========

ThüCAT is "Thüringer Content Architektur Tourismus".
This is an extension for TYPO3 CMS (https://typo3.org/) to integrate ThüCAT.
The existing API is integrated and allows importing data into the system.

The extension can be used within a multi-site project and restricts itself to the site context. This
means two sites importing ThueCat data will not interfere with one another: the elements of one site
are not displayed in the context of the other. This holds true for both frontend and backend
listings.

The extension supports multiple languages within the site. It respects the configured languages
during import and provides all translated data the service offers. Language information coming from
ThueCat in a language the importing site does not define is ignored.

Table of Contents
=================

.. toctree::
   :maxdepth: 1
   :titlesonly:

   Features
   Integration/Index
   Developers/Index
   Changelog
   Sitemap
