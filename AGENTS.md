# EXT:thuecat — Repository Guidelines

Conventions for coding agents working in this package.

This package supports **TYPO3 v14.3** (`^14.3`) on PHP 8.4 and 8.5.

## Coding style

- Non-final classes expose `protected`, never `private`. Extensions are extended; `private` is for
  `final` classes only. A helper that "belongs on the abstract" usually does — check whether it
  already exists there before adding a private copy, because a private helper is how three copies
  of the same logic happen. Thin delegating wrappers are fine.
- Comments state **why**, or how to use something. The code already shows what it does. No prose,
  no narration of history or corrections.
- Every thrown exception carries a unique integer code — the unix timestamp of the moment it was
  written (`date +%s`). Never copy one.
- Exceptions extend `RuntimeException`, never `\Exception` or a narrower SPL class.
- A `::class` reference needs a matching `use`. Without one it passes `php -l` and silently
  resolves to the global namespace; config and array files are the riskiest place for this.
- No `@`-operator, and no `assert()` outside tests.
- Scalar parameters use `Connection::PARAM_*`, never `\PDO::PARAM_*`. Lists use
  `ArrayParameterType::*`; DBAL 4 removed `Connection::PARAM_*_ARRAY`.

## Fluid

- Templates use the `.fluid.html` extension; new ones follow suit.
- There is no `f:in` ViewHelper. Use `f:for`, or precompute in the controller.

## Database and TCA

- Never guess a column name. `l18n_parent` and `l10n_parent` differ per table, and a wrong name
  matches nothing **silently**. Read the schema through `TcaSchemaFactory`.
- `QueryBuilder` and `DataHandler` are stateful. Build a fresh instance per use; never inject,
  store, return one from a helper, or reuse one in a loop. `expr()` and `createNamedParameter()`
  must come from the same instance.
- `Connection::select()` filters deleted rows. In tests use `getQueryBuilderForTable()` with
  `removeAll()->add(new DeletedRestriction())` — never bare `removeAll()`, and never read absence
  as proof of a hard delete.
- Before changing a `showitem`, check `overrideChildTca` and `columnsOverrides` for that table. An
  override replaces the `showitem` as a whole and silently keeps the old field list.
- `ExtensionConfiguration::get()` writes globals: a missing path triggers a sync from
  `ext_conf_template.txt`, so a declared key is never truly absent.

## Testing

### Functional test bases

Every functional test extends one of these bases, never core's `FunctionalTestCase` directly.

| Base | Extends | Provides |
|---|---|---|
| `AbstractFunctionalTestCase` | core | the extensions thuecat needs to boot, the codappix data set trait, record read helpers (`countRows`, `fetchUidByRemoteId`, `fetchRowByRemoteId`, …) |
| `AbstractBackendTestCase` | functional | backend user, `LANG`, the `Fixtures/Import/Sites` site configurations |
| `AbstractImportTestCase` | backend | Guzzle faker, file access and media stubs, vocabulary seed, error log writer, empty-log assertion, fetch and import helpers |
| `AbstractFrontendTestCase` | functional | the `Fixtures/Frontend/Sites` site configurations, data set, root page TypoScript, request helpers |
| `AbstractCachingTestCase` | frontend | a storing `pages` cache backend, DataHandler save and delete helpers |
| `AbstractSyncChannelImportTestCase` | import | the `syncChannel` fixture with a fixed window, list and record URLs, channel staging |
| `AbstractImportConfigurationTestCase` | functional | extension configuration backup, site settings writer |

- Extend the narrowest base whose setup and helpers the test uses. A test that runs no import
  does not extend the import base.
- A need shared by several test classes belongs in a base; a need of one class stays in that class.
- `$coreExtensionsToLoad`, `$testExtensionsToLoad`, `$pathsToLinkInTestInstance` and
  `$configurationToUseInTestInstance` are properties, never assigned in `setUp()`. Each base
  publishes its lists as constants. To add to one, redeclare the property and spread the constant
  of the base the class extends:

  ```php
  protected array $coreExtensionsToLoad = [...AbstractImportConfigurationTestCase::CORE_EXTENSIONS, 'scheduler'];
  ```
- `werkraummedia/events` is loaded by the functional base. TYPO3 does not enforce the dependency,
  so a list that drops it does not fail on that account.

### Conventions

- Tests render **this package's own stub templates**. Never load another package's template
  overrides into a test; add whatever output the assertion needs to the stub instead.
- Test doubles extend production classes, so a signature change fatals the suite at bootstrap.
  Grep `extends <ClassName>` under `Tests/` before changing a constructor or method signature.
- Prefer declarative CSV fixtures for new suites; existing suites standardise on the codappix
  trait and stay as they are for consistency.
- Expected dates come from the data, never from "now", and use a named zone rather than a fixed
  offset so DST cannot flip the suite.
- An assertion can record a bug as expected output. Check the fixture's source before suspecting
  the code.
- Run an unskipped test before analysing it. Empirical failure beats reading the diff.
- Expect an exception message with `expectExceptionMessageIsOrContains()`, never `expectExceptionMessage()`; Rector
  rewrites the latter and fails CI.

### Coverage and mutation score

- New and changed code reaches **100% coverage and 100% MSI**, counted over the unit and functional
  suites together, with **no skipped mutants**. A score without coverage says nothing, so uncovered
  lines count as escaped.
- Report both numbers for the new code when a change is done.
- `Build/Scripts/infection.sh` runs Infection on `infection.json5` over the coverage of all suites in
  `.coverage/` (`coverage-xml/`, `junit.xml`); produce that with one PHPUnit run of every suite first.
  Without `--full` it is the gate: only lines changed against `origin/main` (`INFECTION_BASE`),
  uncovered code counted, 100% MSI, a 600 s per-mutant timeout, and any skipped mutant fails it. A
  skip means the covering tests outran the timeout; it is not a pass.
- `--full` mutates everything with the configured timeout and no threshold. Over the whole package
  (no further options) it ends with `Build/Scripts/check-quality-baseline.php`, which fails when line
  coverage or MSI fall below the baseline or the number of uncovered lines rises above it.
- CI checks every pull request's line coverage against the baseline and runs the gate. The full run,
  and with it the MSI baseline, is run locally: the CI runner is too small for it.
- A change is not closed before a full run over the whole package has passed. In a planned change it
  is one of the last tasks of the QA group. Only the maintainer can waive it, and only on explicit
  request; never skip it unasked.
- `ProtectedVisibility` is off: non-final classes expose `protected` by design.
- The whole package is held at its recorded baseline (`Build/quality-baseline.json`): no change may lower the total
  line coverage or the total MSI, or add uncovered lines. Legacy code below 100% is tolerated, never imitated. Update
  the baseline when a change improves a value.
- Skipped mutants come from slow tests, not from missing ones. Infection skips by the run time of the whole
  covering test class. Make the tests faster; never raise the timeout or add tests only to get under it.

### Frontend tests in a non-default language

Three things must be right at once; missing one fails in a way that points at the wrong layer.

1. **Request by slug URL.** `InternalRequest::withLanguageId()` sets the v8 `L` parameter, which
   site routing ignores — the page shell renders translated while records stay default-language.
   Use `pageRequest($pageId, $languageId)` / `detailRequest(..., $pageId, $languageId)` on
   `AbstractFrontendTestCase`, which build the URL from the site's language base plus the page
   slug. Always pass the **default-language page uid**; the language aspect resolves the overlay.
2. **Translate the whole chain in the fixture**: the requested page, the storage folder holding the
   records, the storage folder holding `sys_category` rows, the categories, the records, and the
   content element. A partial chain is worse than none — a page-only translation makes the content
   element disappear. Use `l10n_parent` (pages) / `l18n_parent` (tt_content, our tables) only;
   `l10n_source` and `l10n_diffsource` are provenance and change no resolution. Translated content
   rows sit on the **default-language page uid**; only `pages` gets a row of its own.
3. **Fetch single records with `findByUid()`.** Core already configures it for this case. A
   uid-matching query cannot return a translated record: the language constraint demands the row's
   own language, so the default-language row fails the language test and its translation fails the
   uid test. Never work around this with `setRespectSysLanguage(false)` in our repositories.

Assertions: translated labels differ. The empty-state message is "Keine Daten vorhanden." in
German and "No data to show." in English — asserting the German string in an English request
passes on an empty page.

## Quality assurance page

`Documentation/Developers/QualityAssurance.rst` mirrors the QA setup and must stay true. A change to
a tool, its configuration, a `Build/` script, the CI workflow or a baseline's handling updates the
page in the same change. The Deptrac section mirrors `deptrac.yaml`: a layer, a collector's reach or
a rule that changes there changes in the table and the guarantees below it too.

## Static analysis

The PHPStan baseline is generated, never hand-edited: use the `--generate-baseline` command.
`$GLOBALS` errors get baselined; version-dependent ignores belong in `phpstan.neon`, where
regeneration cannot drop them.
