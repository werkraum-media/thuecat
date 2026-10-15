.. include:: ../../Includes.txt
.. _frontend-output-xml-sitemap:

===========
XML sitemap
===========

EXT:thuecat provides one XML sitemap per detail record kind for the sitemap of
:ref:`typo3/cms-seo:xmlsitemap`: one for tourist attractions, one for trails.

.. contents:: On this page
   :local:
   :depth: 1

.. _frontend-output-xml-sitemap-configuration:

Configuration
=============

Depend on the ``typo3/seo-sitemap`` site set in your site set. It provides the sitemap page type and
serves the sitemap index at :file:`/sitemap.xml` under each site's base; no static route is needed:

.. code-block:: yaml
   :caption: EXT:myextension/Configuration/Sets/MySet/config.yaml

   name: myvendor/my-set
   label: 'My set'
   dependencies:
     - 'typo3/seo-sitemap'

Add one sitemap per kind. The provider takes the kind alone:

.. code-block:: typoscript
   :caption: EXT:myextension/Configuration/Sets/MySet/setup.typoscript

   plugin.tx_seo.config.xmlSitemap.sitemaps {
       attraction {
           provider = WerkraumMedia\ThueCat\Frontend\Sitemap\DetailRecordSitemapDataProvider
           config.kind = attraction
       }
       trail {
           provider = WerkraumMedia\ThueCat\Frontend\Sitemap\DetailRecordSitemapDataProvider
           config.kind = trail
       }
   }

.. _frontend-output-xml-sitemap-what-a-sitemap-lists:

What a sitemap lists
====================

The provider takes the table, the detail page and the plugin argument from the kind and the current
site:

* the records stored in the site's page tree, and no others;
* in a translated language, the translations, with URLs under that language's base, and no
  untranslated record;
* no hidden or deleted record;
* each record on the kind's detail page, the site setting ``thuecat.pois.pid_show`` or
  ``thuecat.trails.pid_show``.

A kind whose detail page setting is 0 or unset gets an empty sitemap, and the sitemap index leaves
it out.

With the route enhancers imported and the page role set on the detail pages, the URLs are the
readable detail URLs, see :ref:`frontend-output-routing`.

.. _frontend-output-xml-sitemap-updates:

Updates
=======

A record created, changed or deleted in the backend or by an import shows up on the next sitemap
request; the sitemap does not wait for its cache period.
