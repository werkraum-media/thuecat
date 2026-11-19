.. include:: ../Includes.txt
.. _removal-module:

======================
Removing imported data
======================

.. contents:: On this page
   :local:
   :depth: 1

The backend module :guilabel:`ThüCAT > Remove imported data` removes imported tourist attractions
(POIs), places, trails and events together with what only they use. It runs the removal in the
background, one record after the other, so a large removal neither hits a request's time limit nor
loads the server with one big DataHandler run.

.. _removal-module-access:

Access
======

The module is a regular module permission. Grant it to a backend user group among the group's
allowed modules, like the imports module. A removal deletes with the rights of
the user who started it: records on pages the user may not edit, of tables the user may not modify,
and files outside the user's file mounts or permissions are kept, and the refusal is logged.

The module lists only pages the user may read.

.. _removal-module-selection:

What can be removed
===================

The module lists, grouped by site, every page holding POIs, places, trails or events, with the
number of each kind. Per site it also lists the import configurations the site's records originate
from, by the title the records stored, so a configuration deleted since is still listed.

An editor chooses one removal:

*  All records of one kind from a single folder, a site or the whole instance. Every record of that
   kind counts, whether an import wrote it or an editor created it.
*  Reverting one import: every POI, place, trail and event of a site that originates from the
   chosen import configuration.

The confirmation shows the number of records the removal covers and offers two options:

:guilabel:`Also delete files nothing uses any longer`
   Deletes the images and other files of the removed records once no other record references
   them. Deleted files cannot be restored.

:guilabel:`Update the reference index first`
   Brings the reference index up to date before the first record is removed, see
   :ref:`removal-module-reference-index`. Enabled by default.

Nothing is removed in the request. The confirmation queues a job, which the
:ref:`message worker <removal-module-worker>` takes from there.

.. _removal-module-what-goes:

What a removal deletes
======================

Each record is deleted through the DataHandler, so its translations, inline children (addresses,
opening hours, dates and so on) and file references go with it. Deleted records stay in the database
as soft-deleted rows until a cleanup task removes them, see :ref:`maintenance-cleanup-tasks`.

Organisations, towns, tourist information, parking facilities, meeting rooms, event organizers and
event locations the removed record referenced are deleted too when no other record references them
any longer. A relation still used by another record, or linked from a content element's text, is
kept. Categories and keywords are never deleted, and neither is a POI, place, trail or event that a
removed record merely referenced.

.. _removal-module-reference-index:

Reference index
---------------

Whether a relation or a file is still in use is read from the reference index. A job therefore
updates the whole reference index before its first record, unless the last full update is less than
24 hours old or the editor unchecked the option. Every full update counts, whether a job, the
``referenceindex:update`` command or the backend ran it.

The import logs are kept out of the reference index: they never keep a record in use, and leaving
them out makes every full update faster.

.. _removal-module-jobs:

Jobs
====

The module lists the jobs in two sections:

:guilabel:`Finished removal jobs`
   Every finished job with its selection, the user who started it, the numbers of removed and
   skipped records, removed relations, removed files and errors, and the logged errors. When the
   oldest finished job is more than ten days old, the section links to the
   :ref:`recommended cleanup tasks <maintenance-cleanup-tasks>`.

:guilabel:`Queued and running removal jobs`
   Every job not finished yet, with its status and the numbers so far. A job queued, or running
   without progress, for more than five minutes is marked :guilabel:`stalled`. When more than two
   jobs are stalled, the section points to the setup of the message worker.

Errors of a job are also written to the system log (:guilabel:`Administration > Log`) under the user who
started it: refused record deletions by the DataHandler, refused file deletions and failed steps of
the job. A failed step that stopped the job is retried once a minute, so the job resumes by itself
once the cause, for example a lost database connection, is gone.

.. _removal-module-worker:

Message worker task
===================

The extension sends the jobs to the asynchronous ``doctrine`` transport of the
:ref:`message bus <t3coreapi:message-bus>`. They are processed by the
:ref:`consume command <t3coreapi:message-bus-consume-command>` ``messenger:consume``. Without a
running consumer, jobs stay queued and the module marks them as stalled.

Where no service manager such as systemd runs the command, run it from the
:ref:`scheduler <t3coreapi:symfony-console-commands-scheduler>`:

#. Make sure cron starts the scheduler every minute, see
   :ref:`typo3/cms-scheduler:cron-job`.

#. In :guilabel:`Administration > Scheduler`, add a task of the type :guilabel:`messenger:consume`:

   *  the receiver ``doctrine``
   *  the time limit (``--time-limit``) ``50``, below the one-minute interval of the cron job, so the
      run ends before the next one starts
   *  :guilabel:`Frequency`: every minute (``* * * * *``)
   *  :guilabel:`Allow Parallel Execution`: off, so one consumer handles the messages in the order
      they were sent

A run handles messages back to back until its time limit, so a job moves as fast as the deletions
take, not once per minute.

.. _removal-module-worker-stuck:

A run that does not end
-----------------------

When a run is killed, for example by a server restart or the memory limit, the scheduler still marks
the task as running. Without parallel execution every later run is then skipped, and no job moves.
Open :guilabel:`Administration > Scheduler` and use :guilabel:`Stop` on the task. This only marks the task as
not running; make sure no ``messenger:consume`` process is left before.

A message that was being handled when the run was killed is handed out again by the transport after
its redelivery timeout, so the job goes on where it stopped.

.. _removal-module-worker-routing:

Routing
-------

The extension routes only its own messages to ``doctrine``, in its :file:`ext_localconf.php`. A
project that routes these messages elsewhere wins over that. Routed to the synchronous ``default``
transport, a removal runs in the request again, with its time limit.
