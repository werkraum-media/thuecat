.. include:: ../../Includes.txt

.. _frontend-output-meeting-venues:

==============
Meeting venues
==============

Conference hotels and meeting and congress locations are tourist attractions of the record type
``meeting_venue``. They appear in every attraction list, render through the attraction templates,
and carry the meeting data and meeting rooms on top.

.. contents:: On this page
   :local:

.. _frontend-output-meeting-venues-record-type:

Record type
===========

Every attraction carries :fluid:`{attraction.recordType}`: ``attraction`` or ``meeting_venue``. A
meeting venue is a ``MeetingVenue`` model, a subclass of ``TouristAttraction``, so every getter of
an attraction works for it too. Lists can be restricted to record types, see
:ref:`frontend-output-plugin-settings-record-types`.

.. _frontend-output-meeting-venues-templates:

Templates
=========

There is no template of its own. The attraction templates render venue output inside a record type
condition, through partials:

.. code-block:: html
   :caption: EXT:my_extension/Resources/Private/Templates/TouristAttraction/Show.fluid.html

   <f:if condition="{attraction.recordType} == 'meeting_venue'">
       <f:render partial="TouristAttraction/MeetingVenue" arguments="{venue: attraction}" />
       <f:render partial="TouristAttraction/MeetingRoomTable" arguments="{rooms: attraction.meetingRoomTable}" />
       <f:render partial="TouristAttraction/Venue/MeetingRoomDetails" arguments="{rooms: attraction.meetingRoomTable}" />
       <f:render partial="TouristAttraction/Venue/Lodging" arguments="{venue: attraction}" />
   </f:if>

The stub :file:`TouristAttraction/Show` calls every venue partial under
:file:`Partials/TouristAttraction/Venue/`; :file:`TouristAttraction/ListItem` calls
:file:`TouristAttraction/MeetingVenueListItem`. A template override that lacks the condition renders
a venue like any other attraction.

.. _frontend-output-meeting-venues-model:

Venue data
==========

The venue fields come in groups, one partial each: meeting, lodging, gastronomy, visit, building,
links, texts, relations, media and inline parking facilities. Some getters prepare the stored value:

``getMeetingFacilityMembers()`` and the other ``get…Members()`` getters
   The stored list as an array. An enum member is its ontology name, translated by the key
   ``content.<field>.<member>`` in :file:`locallang.xlf`; see :file:`Venue/Fact/Members`.

``getDistances()``
   The distances that are set, keyed ``airport``, ``cityCenter``, ``fair``, ``freeway`` and
   ``iceStation``. Each is an array of ``value`` and ``unit``: kilometres from 1000 metres on,
   metres below.

``getAdditionalType()``
   The ThueCat type URI of the venue, for structured data.

.. _frontend-output-meeting-venues-rooms:

Meeting rooms
=============

``getMeetingRooms()`` returns the rooms the venue names, in upstream order.
``getMeetingRoomTable()`` returns every room once: each room of the venue, followed by the rooms
reached only through it, such as the parts of a divisible hall. Render the room table and the room
details from it; templates need no graph work.

Meeting rooms have no detail page. The stub room table links each room to its details further down
the page, at the anchor ``#meeting-room-<uid>``.

The detail page cache is tagged with the rooms it rendered, so an import changing a room refreshes
the venue page.
