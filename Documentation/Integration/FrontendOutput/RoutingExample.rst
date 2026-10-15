.. include:: ../../Includes.txt
.. _routing-example:

===============
Routing example
===============

A routed attraction detail page, from the content element to the URL. Replace ``myvendor`` with
your own vendor name.

.. _routing-example-the-content-element:

The content element
===================

The attraction detail content element wires its ``CType`` to the ``TouristAttractionShow`` plugin,
as all content elements do, see :ref:`content-element-example`:

.. code-block:: typoscript
   :caption: EXT:myextension/ContentBlocks/ContentElements/attraction-show/setup.typoscript

   tt_content.myvendor_attractionshow =< lib.contentBlock
   tt_content.myvendor_attractionshow {
       20 =< lib.myvendorThuecatPlugin
       20 {
           pluginName = TouristAttractionShow
       }
   }

.. _routing-example-the-route:

The route
=========

The site configuration imports the route of the detail plugin:

.. code-block:: yaml
   :caption: config/sites/<your-site>/config.yaml

   imports:
     - { resource: 'EXT:thuecat/Configuration/Routes/TouristAttractionShow.yaml' }

.. _routing-example-the-page-role:

The page role
=============

The page carrying the content element gets :guilabel:`Tourist attraction detail` as its
:guilabel:`ThüCAT page role`. Its uid is the site setting ``thuecat.pois.pid_show``, so list links
and the sitemap point there.

.. _routing-example-the-mapping:

The mapping
===========

The site set's page TSconfig maps the content element type to the role, so the page module tells
editors when the role is missing:

.. code-block:: typoscript
   :caption: EXT:myextension/Configuration/Sets/MySet/page.tsconfig

   tx_thuecat.pageRoles.myvendor_attractionshow = thuecat_touristattractionshow

.. _routing-example-the-result:

The result
==========

A list linking to the attraction "Stadtmuseum Erfurt" on the detail page :file:`/ausflugsziel`
produces :file:`/ausflugsziel/stadtmuseum-erfurt`, and that URL renders the attraction.
