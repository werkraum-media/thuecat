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

and relate those to Tourist Attractions.

Frontend
========

The extension offers no frontend plugins, only registrations for Extbase actions to deliver the
data.
How to make use of this is described in :ref:`content-element-example`.
The data types supported for listing and display are Tourist Attraction and Trail.
Attraction lists show their records by title, or in the order editors arranged them in the backend.

Backend
=======

Like usual, :ref:`configuration` is done in backend and executed via :ref:`import` with CLI.

The extension comes with a backend module that displays the logs written by every import. It shows
the status, the mapping success of incoming values, suspicious upstream data that could not be
converted into usable data, and other information.

Scheduler and CLI
=================

To import ThueCat data into the local system, several kinds of import configurations can be
configured and executed as CLI commands. These can also be wrapped into scheduler tasks to run on a
predefined schedule. See :ref:`import` for further information.

Developers Corner
=================

Details about how things are implemented within the extension are available in :ref:`developers` and
can be used to extend and change behaviour as needed.