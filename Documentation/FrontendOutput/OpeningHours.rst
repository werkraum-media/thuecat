.. _frontend-output-opening-hours:

=============
Opening hours
=============

.. _frontend-output-opening-hours-import:

Import
======

Opening hours are imported as inline database records (one row per weekday and
time span, with an optional validity range). You do not work with those rows
directly: the model computes a display-ready shape from them.

.. _frontend-output-opening-hours-model:

Computed model
==============

A tourist attraction exposes two computed accessors:

* ``computedOpeningHours`` -- the regular hours.
* ``computedSpecialOpeningHours`` -- deviating hours, for example public holidays.

Both return the same structure: a list of **periods** (validity ranges), each
listing **weekdays** Monday-first, each weekday carrying **all** of its time
spans. A day without hours is marked closed, so every weekday is present in the
output. Past periods are dropped; the period covering today is flagged as current.

.. note::

   Whether the attraction is *open right now* is intentionally **not** computed
   server-side -- it depends on the visitor's time zone. Resolve the current
   open/closed state in client-side logic if you need it.

.. _frontend-output-opening-hours-partial:

Rendering with the shipped partial
==================================

The extension ships a partial that renders the computed shape as a per-day table
(one row per weekday, all spans listed, closed days shown as closed). Render it
from your ``Show`` template, passing the computed hours and a heading label:

.. code-block:: html

   <f:render partial="OpeningHours/PerDayTable" arguments="{
       openingHours: attraction.computedOpeningHours,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.openingHours'
   }" />
   <f:render partial="OpeningHours/PerDayTable" arguments="{
       openingHours: attraction.computedSpecialOpeningHours,
       heading: 'LLL:EXT:thuecat/Resources/Private/Language/locallang.xlf:content.specialOpeningHours'
   }" />

The partial takes two arguments:

==================  ==========================================================
Argument            Meaning
==================  ==========================================================
``openingHours``    A computed opening hours object (one of the accessors above).
``heading``         A translation key for the section heading.
==================  ==========================================================

The partial renders nothing when there are no periods, so a missing heading or
empty section never appears.

.. note::

   The partial set will grow with further output designs. Names such as
   ``OpeningHours/PerDayTable`` may still change while the set settles -- check
   this section after upgrades.