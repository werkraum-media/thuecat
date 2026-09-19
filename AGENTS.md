# EXT:thuecat — Repository Guidelines

Conventions for coding agents working in this package. Instance-wide guidance, including the ddev
commands, lives in the repository root `AGENTS.md`.

This package is a **git submodule** and supports **TYPO3 v13.4 and v14** (`^13.4 || 14.*`); CI
tests both. Core source for either version is worth reading directly when behaviour differs
between them; where a checkout lives is a local matter and not recorded here.

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
- `Connection::PARAM_*`, not `\PDO::PARAM_*`.

## Fluid

- Template extension depends on the TYPO3 range: this package supports v13, so templates are plain
  `.html`. Only v14-only packages (`*_ces`, `sitepackage`) use `.fluid.html`.
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
- `ExtensionConfiguration::get()` writes globals: a missing path triggers a sync from
  `ext_conf_template.txt`, so a declared key is never truly absent.

## Testing

- Functional tests must load `werkraummedia/events` in `testExtensionsToLoad`; v13 enforces the
  dependency, v14 tolerates its omission.
- Tests render **this package's own stub templates**. Never load `*_ces` or `sitepackage` into a
  test; add whatever output the assertion needs to the stub instead. Template bugs get fixed in
  both the stub and the sitepackage override.
- Content Block definitions and `*_ces` partials have **no test coverage**. Changing a base
  partial's contract means changing the `*_ces` copy too, and verifying in the running site.
- Test doubles extend production classes, so a signature change fatals the suite at bootstrap.
  Grep `extends <ClassName>` under `Tests/` before changing a constructor or method signature.
- Prefer declarative CSV fixtures for new suites; existing suites standardise on the codappix
  trait and stay as they are for consistency.
- Expected dates come from the data, never from "now", and use a named zone rather than a fixed
  offset so DST cannot flip the suite.
- An assertion can record a bug as expected output. Check the fixture's source before suspecting
  the code.
- Run an unskipped test before analysing it. Empirical failure beats reading the diff.

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

## Static analysis

The PHPStan baseline is generated, never hand-edited: use the `--generate-baseline` command.
`$GLOBALS` errors get baselined; version-dependent ignores belong in `phpstan.neon`, where
regeneration cannot drop them.
