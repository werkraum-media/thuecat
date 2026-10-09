.. include:: ../../Includes.txt
.. _frontend-output-opening-hours:

=============
Opening hours
=============

.. _frontend-output-opening-hours-import:

Import
======

Opening hours are imported as inline database records (one row per weekday and time span, with an
optional validity range). You do not work with those rows directly: the attraction computes
display-ready shapes from them, and the extension ships one partial per shape for direct use or
adaption as needed.

.. _frontend-output-opening-hours-options:

Display options
===============

Three display options are available. Pick one for your :file:`TouristAttraction/Show` template; the
shipped stub renders all three side by side so you can compare them against real data.

.. list-table::
   :header-rows: 1

   *  - Partial
      - Accessors
      - Displays
   *  - :file:`OpeningHours/PerDayTable`
      - ``perDayTable``, ``specialPerDayTable``
      - Every weekday on its own line, closed days included.
   *  - :file:`OpeningHours/MergedByWeekday`
      - ``mergedByWeekday``, ``specialMergedByWeekday``
      - Weekdays with identical hours on one line, listed one by one.
   *  - :file:`OpeningHours/MergedByWeekdayRanges`
      - ``mergedByWeekday``, ``specialMergedByWeekday``
      - Like ``MergedByWeekday``, consecutive days collapsed into ranges.

Each display has two accessors: the first holds the regular hours, the ``special`` one the deviating
hours, for example public holidays. Render the partial once for each.

For an attraction open Monday to Friday 10:00–18:00 and Saturday 10:00–14:00, the three displays
show:

.. code-block:: text
   :caption: PerDayTable

   Monday:    10:00–18:00
   Tuesday:   10:00–18:00
   Wednesday: 10:00–18:00
   Thursday:  10:00–18:00
   Friday:    10:00–18:00
   Saturday:  10:00–14:00
   Sunday:    closed

.. code-block:: text
   :caption: MergedByWeekday

   Monday, Tuesday, Wednesday, Thursday, Friday: 10:00–18:00
   Saturday: 10:00–14:00

.. code-block:: text
   :caption: MergedByWeekdayRanges

   Monday–Friday: 10:00–18:00
   Saturday: 10:00–14:00

.. _frontend-output-opening-hours-per-day-table:

PerDayTable
-----------

.. code-block:: html

   <f:render partial="OpeningHours/PerDayTable" arguments="{
       openingHours: attraction.perDayTable,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.openingHours'
   }" />
   <f:render partial="OpeningHours/PerDayTable" arguments="{
       openingHours: attraction.specialPerDayTable,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.specialOpeningHours'
   }" />

* All seven weekdays are always listed, Monday first. A day without hours shows as closed.
* A day with several spans lists them all, separated by commas, for example ``08:00–12:00,
  13:00–18:00``.
* Public holidays appear as a last line only when upstream supplies hours for them.

Choose it when visitors should see at a glance on which days the attraction is closed.

.. _frontend-output-opening-hours-merged-by-weekday:

MergedByWeekday
---------------

.. code-block:: html

   <f:render partial="OpeningHours/MergedByWeekday" arguments="{
       openingHours: attraction.mergedByWeekday,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.openingHours'
   }" />
   <f:render partial="OpeningHours/MergedByWeekday" arguments="{
       openingHours: attraction.specialMergedByWeekday,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.specialOpeningHours'
   }" />

* Weekdays sharing exactly the same spans form one line. The days need not be adjacent: ``Monday,
  Wednesday, Friday: 08:00–12:00`` is one line.
* Closed days are left out entirely. Nothing says that the attraction is closed on Sunday; the day
  is simply missing.
* Public holidays always form a line of their own, last, even when their hours match a weekday
  group.

Choose it for compact output where the hours are regular.

.. _frontend-output-opening-hours-merged-by-weekday-ranges:

MergedByWeekdayRanges
---------------------

.. code-block:: html

   <f:render partial="OpeningHours/MergedByWeekdayRanges" arguments="{
       openingHours: attraction.mergedByWeekday,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.openingHours'
   }" />
   <f:render partial="OpeningHours/MergedByWeekdayRanges" arguments="{
       openingHours: attraction.specialMergedByWeekday,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.specialOpeningHours'
   }" />

This partial uses the same accessors and the same grouping as ``MergedByWeekday``. The only
difference is how a line names its days: a run of consecutive days is shortened to its first and
last day. `Monday, Tuesday, Wednesday, Thursday,
Friday` becomes :output:`Monday–Friday`; a group of Monday, Tuesday and Thursday becomes
:output:`Monday–Tuesday, Thursday`.

Closed days and public holidays behave as in ``MergedByWeekday``. Ranges do not wrap around the
week: Sunday and Monday are never joined.

.. _frontend-output-opening-hours-common:

What all partials share
=======================

All three partials take the same two arguments:

================== ========================================================== Argument Meaning
================== ========================================================== ``openingHours`` One
of the accessors listed for that partial. ``heading`` A translation key for the section heading.
================== ==========================================================

The partials render nothing when there are no hours, so an empty section and its heading never
appear. This is what lets you render the special hours unconditionally.

Opening hours can be limited to a validity range, for example summer and winter hours. Every partial
handles them the same way:

* Each range is rendered as a block of its own, headed by its dates (``01.04.2026 – 31.10.2026``, a
  start date alone, or ``until 31.10.2026``). Hours without a range get no date line.
* Ranges that have ended are not shown.
* The range covering today comes first. Later ranges follow by start date, each marked
  :guilabel:`Upcoming`.

Weekday names, :guilabel:`closed`, :guilabel:`Upcoming` and :guilabel:`until` come from
:file:`EXT:thuecat/Resources/Private/Language/locallang.xlf`, keys ``content.openingHour.weekday.*``
and ``content.openingHours.*``.

.. important::

   Which range is current, which ranges have ended, and therefore what the partials show, is decided
   when the page is rendered. The detail page is cached, so the output stays as it was rendered
   until its page cache entry expires or is cleared.

.. _frontend-output-opening-hours-custom:

Writing your own partial
========================

Override a partial through ``partialRootPaths`` as described in :ref:`frontend-output`, keeping its
name, or add one of your own. The accessors provide the following shapes.

``perDayTable`` and ``specialPerDayTable``:

.. code-block:: text

   periods[]            validFrom, validThrough (both optional), current
     weekDays[]         dayOfWeek, closed
       timePeriods[]    opens, closes

``mergedByWeekday`` and ``specialMergedByWeekday``:

.. code-block:: text

   periods[]            validFrom, validThrough (both optional), current
     weekDayGroups[]    daysOfWeek (list of day names)
       dayRanges[]      firstDay, lastDay, range
       timePeriods[]    opens, closes

``dayOfWeek``, ``daysOfWeek``, ``firstDay`` and ``lastDay`` hold the English day name (``Monday`` …
``Sunday``, ``PublicHolidays``), which is the suffix of the weekday translation key. ``opens`` and
``closes`` are date objects; format them with :fluid:`f:format.date(format: 'H:i')`. ``range`` is
false for a single day.

.. note::

   Both shapes also carry ``openNow``. It is evaluated in the server's time zone when the page is
   rendered and then cached with the page, so it is not reliable for visitors. Resolve the current
   open or closed state client-side if you need it.