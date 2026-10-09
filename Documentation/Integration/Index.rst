.. include:: ../Includes.txt
.. _integration:

===============
For Integrators
===============

Information about how to install and use the extension within a TYPO3 instance.

.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :card-height: 100

   .. card:: :ref:`installation`

      How to get the extension and integrate within the instance.

   .. card:: :ref:`configuration`

      What settings are available and how they resolve to a working instance.

   .. card:: :ref:`frontend-output`

      How to provide content elements to display the imported data. The extension does not define
      frontend output, this will be left to the integrator to provide properly fitting templates to
      seamlessly integrate within the site. There are templates and partials to get inspiration and
      syntax from, though.

   .. card:: :ref:`import`

      How to get data into the instance via import configuration and scheduled tasks.

.. toctree::
   :hidden:

   Installation
   Configuration
   BackendRelationScoping
   Import
   FrontendOutput/Index
