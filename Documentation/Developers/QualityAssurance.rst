.. include:: ../Includes.txt
.. _developers-quality-assurance:

=================
Quality assurance
=================

Every tool below is a development dependency of the package and runs from its root directory with
:file:`vendor/bin/`. Configuration files live in the package root, helper scripts in
:file:`Build/`. None of them ship with an installed extension.

.. _developers-quality-assurance-overview:

Overview
========

.. list-table::
   :header-rows: 1
   :widths: 20 30 15 35

   * - Tool
     - Configuration
     - executed on Pull requests
     - Fails on
   * - Composer
     - :file:`composer.json`
     - yes
     - an invalid or not strictly valid manifest
   * - XLIFF schema
     - :file:`xliff-core-1.2-strict.xsd`
     - yes
     - a language file that violates XLIFF 1.2 strict
   * - PHP lint
     - –
     - yes
     - a syntax error on any supported PHP version
   * - PHP CS Fixer
     - :file:`.php-cs-fixer.dist.php`
     - yes
     - a file the fixer would change
   * - Rector
     - :file:`rector.php`
     - yes
     - a file Rector would change
   * - PHPStan
     - :file:`phpstan.neon`, :file:`phpstan-baseline.neon`
     - yes
     - an error not in the baseline, or a baseline entry that no longer matches
   * - Deptrac
     - :file:`deptrac.yaml`, :file:`deptrac.baseline.yaml`
     - no
     - a dependency the layer rules forbid and the baseline does not list
   * - PHPUnit
     - :file:`phpunit.xml.dist`, :file:`phpunit_dev.xml.dist`
     - yes, PHP 8.4 and 8.5
     - a failing test
   * - Line coverage
     - :file:`Build/quality-baseline.json`
     - yes
     - a total below the baseline
   * - Infection, gate
     - :file:`infection.json5`, :file:`Build/Scripts/infection.sh`
     - yes
     - a changed line below 100 % MSI, uncovered or with a skipped mutant
   * - Infection, full run
     - same, plus :file:`Build/quality-baseline.json`
     - no
     - a total MSI or line coverage below the baseline

A change is ready for review when every tool passes locally. The full mutation run and Deptrac are
not part of the pull request pipeline and are the author's to run.

.. _developers-quality-assurance-validation:

Validation
==========

.. code-block:: shell

   composer validate
   xmllint --schema xliff-core-1.2-strict.xsd --noout $(find Resources -name '*.xlf')
   find *.php Classes Configuration Tests -name '*.php' -print0 | xargs -0 -n 1 php -l > /dev/null

.. _developers-quality-assurance-php-cs-fixer:

PHP CS Fixer
============

The coding guideline, including the licence header every PHP file carries. The header comes from
the custom fixer in :file:`Build/PhpCsFixer/`, so a new file gets it by running the fixer, not by
copying it.

.. code-block:: shell

   vendor/bin/php-cs-fixer fix --dry-run --diff   # check
   vendor/bin/php-cs-fixer fix                    # apply

.. _developers-quality-assurance-rector:

Rector
======

Keeps the code on current PHP, PHPUnit and TYPO3 idioms: the PHP sets, the prepared sets and the
TYPO3 level set up to v14. Every skip in :file:`rector.php` states why the rule does not apply; a new
skip states its reason too.

.. code-block:: shell

   vendor/bin/rector process --dry-run   # check
   vendor/bin/rector process             # apply

.. _developers-quality-assurance-phpstan:

PHPStan
=======

Runs on level ``max`` over :file:`Build/`, :file:`Classes/`, :file:`Configuration/` and
:file:`Tests/`. Unmatched ignores are reported, so a fixed error has to leave the baseline too.

The baseline is generated, never edited by hand. Ignores that depend on a dependency's version belong
in :file:`phpstan.neon`, where regenerating cannot drop them.

.. code-block:: shell

   vendor/bin/phpstan analyse
   vendor/bin/phpstan analyse --generate-baseline

.. _developers-quality-assurance-deptrac:

Deptrac
=======

Guards the dependencies between the package's parts. The package provides an import and a
representation of the imported data, the latter split into frontend and backend. Each of these is
divided into layers, and the rules say which layer may use which.

.. list-table::
   :header-rows: 1
   :widths: 25 75

   * - Layer
     - May use
   * - ImportCommand
     - ImportEngine, ImportModel, ImportRepository
   * - ImportEngine
     - ImportModel, ImportRepository, Events
   * - ImportIntegration
     - ImportEngine, ImportModel
   * - ImportRepository
     - ImportModel
   * - ImportModel
     - –
   * - FrontendDelivery
     - FrontendRepository, FrontendModel
   * - FrontendRepository
     - FrontendModel
   * - FrontendModel
     - Events
   * - BackendModule
     - ImportModel, ImportRepository
   * - BackendForms
     - –
   * - Service
     - –
   * - System
     - every layer

The table lists what a layer may use. In addition, every layer may use Service, while no layer may
use System. What these rules ensure:

*  A model never uses a repository.
*  Import and frontend never use each other.
*  The backend sees the import's data, never the import itself.
*  Service holds helpers without side effects and the vocabulary several features share. It may
   depend on nothing in the package, which is what lets every feature use it instead of keeping a
   copy.
*  System holds the upgrade wizards. They change persisted data and are run by the install tool, never
   by the package's own code.
*  Events stands for :composer:`werkraummedia/events`. Only its use from this package is checked.

:file:`deptrac.yaml` assigns classes to layers by name, so a class moved to another namespace may
change its layer. Every class of the package belongs to exactly one layer. Dependencies on classes
outside the package and :composer:`werkraummedia/events` are reported as uncovered, which does not
fail a run.

The baseline lists the violations that existed when the rules were introduced. It only ever
shrinks: a fixed violation leaves it, a new one is fixed instead of added.

.. code-block:: shell

   vendor/bin/deptrac analyse
   vendor/bin/deptrac analyse --report-uncovered
   vendor/bin/deptrac analyse --formatter=baseline --output=deptrac.baseline.yaml

Writing the baseline exits with an error whenever the previous one missed a violation. Run the
analysis again afterwards to check the result.

.. _developers-quality-assurance-phpunit:

PHPUnit
=======

:file:`phpunit.xml.dist` defines the ``unit`` and ``functional`` suites. Functional tests run on
SQLite and need the ``de_DE.UTF-8`` and ``en_GB.UTF-8`` locales. :file:`phpunit_dev.xml.dist` is the
strict variant: it fails on deprecations, notices and warnings and shows their details.

Paratest runs the suites in parallel, PHPUnit runs a single file more readably.

.. code-block:: shell

   vendor/bin/paratest --runner=WrapperRunner
   vendor/bin/phpunit --testsuite unit
   vendor/bin/phpunit -c phpunit_dev.xml.dist Tests/Functional/ImporterTest.php

How import tests are written is described in :ref:`developers-import-testing`.

.. _developers-quality-assurance-coverage:

Coverage
========

New and changed code reaches 100 % line coverage. Coverage is always measured over all suites in
one run: a per-suite report misses what the other suite covers, and Infection reads this report.
It needs a coverage driver such as Xdebug.

.. code-block:: shell

   XDEBUG_MODE=coverage vendor/bin/paratest --runner=WrapperRunner \
       --coverage-xml .coverage/coverage-xml --log-junit .coverage/junit.xml
   php Build/Scripts/check-quality-baseline.php

The script compares the total line coverage with :file:`Build/quality-baseline.json`.

.. _developers-quality-assurance-infection:

Infection
=========

Mutation testing checks that the tests notice when the code changes. New and changed code reaches
100 % MSI with no skipped mutant. :file:`Build/Scripts/infection.sh` reads the coverage from
:file:`.coverage/`, so the coverage run comes first.

The gate
   Without options, the script mutates only the lines changed against ``origin/main``
   (``INFECTION_BASE``), counts uncovered code as escaped and requires 100 % MSI. Each mutant gets
   600 seconds (``INFECTION_GATE_TIMEOUT``), and any skipped mutant fails the gate.

The full run
   ``--full`` mutates everything with the timeout from :file:`infection.json5` and no threshold.
   Over the whole package it ends by checking the total MSI and line coverage against
   :file:`Build/quality-baseline.json`. A change is not done before a full run has passed.

.. code-block:: shell

   Build/Scripts/infection.sh
   Build/Scripts/infection.sh --full
   Build/Scripts/infection.sh --full --filter=Classes/Import/Resolver.php

``INFECTION_THREADS`` sets the number of threads, all cores by default.

A skipped mutant means its covering tests outran the timeout. The fix is faster tests, never a
longer timeout.

.. _developers-quality-assurance-baseline:

The quality baseline
====================

:file:`Build/quality-baseline.json` records the package's total line coverage and MSI. No change may
lower either. No tool writes the file: when the check reports a total above the baseline, the
change raises the value by hand.