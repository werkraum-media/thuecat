.. include:: Includes.txt
.. _features:

========
Overview
========

ThueCat as a service provides regional touristic relevant data and their relations via an API. The
thuecat extension, whose documentation manual you are currently reading, uses this data to import it
into a TYPO3 instance. Then, with appropriate content elements, it can be presented to the user for
example to plan their trip into a supported region and miss no hotspot, or find out what events are
running in a desired timeframe. For this to work, ext:thuecat declares a dependency to
:composer:`werkraummedia/events`. This import can't be skipped.

Supported data types
====================

The extension can currently import

* Tourist Attractions
* Trails
* Organisations
* Towns
* Tourist Information

In conjunction with :composer:`werkraummedia/events` it also imports

* Events
* Event Dates

and relate those to Tourist Attractions, Organisations, Towns and Tourist Information, see
:ref:`developers-import-event-place-matching`.

Frontend
========

The extension offers no frontend plugins, only registrations for Extbase actions to deliver the
data.
How to make use of this is described in :ref:`content-element-example`.
The data types supported for listing and display are Tourist Attraction and Trail.
Attraction lists show their records by title, or in the order editors arranged them in the backend.

Detail pages, list filters and the search get readable URLs, see :ref:`frontend-output-routing`.
Detail records are listed in XML sitemaps, see :ref:`frontend-output-xml-sitemap`.

Backend
=======

Like usual, :ref:`configuration` is done in backend, and imports are started there, see
:ref:`import`.

The extension comes with a backend module that starts and stops imports and displays the jobs and
the logs they write. It shows the status, the progress, the mapping success of incoming values, suspicious upstream data that could not be
converted into usable data, and other information.

.. _features-import-jobs:

Import jobs
===========

To import ThueCat data into the local system, several kinds of import configurations can be
configured. Each import runs as a job in the background, started in the backend module or by the
schedule of its configuration, one record at a time. See :ref:`import` for further information.

Events can also be imported from a ThueCat sync channel, including licence-restricted images, see
:ref:`sync-channel`.

Developers Corner
=================

Details about how things are implemented within the extension are available in :ref:`developers` and
can be used to extend and change behaviour as needed.