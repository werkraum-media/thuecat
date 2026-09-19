.. _features:

========
Features
========

* Support multiple languages.

Import
======

Import of specific resources via static URLs.
This allows import of specific tourist attractions and towns.

* The following data types can be imported (not all properties are supported):

  * Organisations (responsible for content)

  * Towns

  * Tourist information

  * Tourist attraction

Backend module
==============

Allows to create new import configurations.

Allows to inspect:

* past imports and possible errors, as well as number of affected records.

.. figure:: /Images/BE-Module-Imports.png
   :align: center

   Figure 1-2: Overview of executed imports and their results.

Frontend
========

The extension offers no frontend plugins, only registrations for extbase actions to deliver the data.
How to make use of this is decribed in :ref:`content-element-example`.
