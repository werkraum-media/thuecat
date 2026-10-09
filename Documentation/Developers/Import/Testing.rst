.. include:: ../../Includes.txt
.. _developers-import-testing:

=================
Testing an import
=================

Import behaviour is tested with functional tests: a real database, a real DataHandler, and upstream
responses served from fixture files instead of the network.

The test case
=============

Import tests extend :php:`WerkraumMedia\ThueCat\Tests\Functional\AbstractImportTestCase`.
Read it before writing the first test; most of what a test needs is already there.

Upstream responses
   Every request a run makes has to be announced. :php:`expectFetch()` serves a JSON-LD file for one
   URL, :php:`expectNotFound()` answers ``404``, :php:`expectFailure()` answers any other status. A
   request nobody announced fails the test, and so does an announced request that never happened. A
   test therefore also documents exactly what a run fetches.

Fixture files
   Response bodies live under :file:`Tests/Functional/Fixtures/Import/Guzzle/`, in a path mirroring
   the URL: host, path, then the resource id as file name.

Database state
   Pages, existing records and configurations are seeded from PHP data sets in
   :file:`Tests/Functional/Fixtures/Import/`; expected states after an import live in
   :file:`Tests/Functional/Assertions/Import/`.

Reading results
   :php:`countRows()`, :php:`fetchUidByRemoteId()` and :php:`fetchRowByRemoteId()` query the
   database ignoring deleted rows, but no other restriction — hidden records and every language
   count. Use them instead of building queries in the test.

:composer:`werkraummedia/events` must be among the extensions a functional test loads. TYPO3 13
enforces the dependency; 14 tolerates its absence, so a test can pass on one version and fail on the
other.

Habits that keep tests honest
=============================

Count the default language
   A table holds translations too. A count over the whole table passes for the wrong reasons;
   look records up by :sql:`remote_id` instead.

Seed instead of importing twice
   A re-import test seeds the rows a previous run would have left and imports once. Running the
   import twice in one test mostly proves that a run does what a run does.

Test removal along with addition
   A new relation needs a test that removes it, and one where a fetch fails next to a surviving
   entry, see :ref:`developers-import-relation-sets`.

Use placeholders for structural roles
   Where a fixture needs a value that must not match anything real — an unmapped type, a foreign
   host — use an obviously invented one, not a real upstream URI.

The PHPUnit configuration ships with the package as :file:`phpunit.xml.dist`.