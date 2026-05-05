# Importer architecture

A short tour of how `Classes/Domain/Import/` is laid out and how a single import flows. Audience: TYPO3 extension developers picking up this codebase. The goal is to cover the moving parts and the boundaries between them, not every method.

## Components

- **`Importer`** — orchestrates one run. Picks a `UrlProvider`, walks every URL, drives the DataHandler loop, returns the run's max log severity.
- **`UrlProvider`** (interface) — produces the list of root URLs for one configuration. Implementations: `StaticUrlProvider` (hand-listed URLs), `SyncScopeUrlProvider` (queries upstream for everything updated in a sync scope), `ContainsPlaceUrlProvider`.
- **`Parser`** + **`Parser\Entity\*`** — pure transformation from a JSON-LD `@graph` to a `DataHandlerPayload`. One `Entity` class per imported record type (`Organisation`, `Town`, `TouristInformation`, `TouristAttraction`, `ParkingFacility`); `TransientEntity\*` covers nested value objects (Address, OpeningHours, Offer, …). `EntityInterface::handlesTypes()` declares which `@type` strings a parser claims; priority breaks ties.
- **`DataHandlerPayload`** — typed bag the parser fills and the resolver/importer drain. Holds four buckets: `dataMap`, `cmdMap`, `transients` (unresolved JSON-LD references), `translations` (per-language partial rows). Plus auxiliary maps for default-language vs. translation rows.
- **`Resolver`** + **`ResolverContext`** — walks the parsed tree, resolves transients (looks up FKs by `remote_id`, fetches missing nodes, re-parses them), drains translations once parents have uids. Visit-once contract is enforced via `ResolverContext::remoteIdToKey`; the default-language and translation status maps short-circuit re-resolution across rounds.
- **`Importer\FetchData`** — HTTP boundary. Caches JSON-LD responses by `(url, apiKey)` SHA. Throws `ResourceNotFoundException` (subclass of `InvalidResponseException`) for upstream 404 so callers can decide whether to drop, stub, or abort.
- **`ImportLogger`** — single producer of `tx_thuecat_import_log` + `tx_thuecat_import_log_entry` rows. Accumulates `savingEntity` rows from the payload plus DataHandler errorLog and caught exceptions, then flushes everything in one DataHandler call. Tracks max severity for the run.

## Flow of one `importConfiguration()` call

1. Resolve the `UrlProvider` and the site's `defaultLanguage` + `translationLanguages` map (from `SiteFinder`).
2. Build a `ResolverContext` that owns the run's state (storagePid, language map, status maps, `remoteIdToKey`).
3. **URL loop** — for each URL the provider returns:
   - Fetch JSON-LD. `InvalidResponseException` here → log as `fetchingError` and continue (run survives one broken root).
   - Run `Parser::parse()`, then `Resolver::resolve()`. Any `Throwable` from this branch → log as `mappingError` and continue.
   - Merge the resolved payload into a single `accumulatedPayload`.
4. Snapshot the default-language datamap for the logger (translation rows are excluded so the savingEntity counts match what users see).
5. **Drain loop** — while `dataMap` or `cmdMap` is non-empty:
   - Fresh `DataHandler` per pass (state doesn't survive `start()`).
   - `process_datamap()` then `process_cmdmap()`.
   - Capture `$dataHandler->errorLog` into the import logger as `dataHandlerError` rows (severity `error`).
   - Merge `substNEWwithIDs`, promote NEW… placeholders in `remoteIdToKey` to real uids via `ResolverContext::promoteNewKeys()`.
   - Re-run `Resolver::resolve()` against the now-empty payload — translations and post-localize fields land here.
   - Iteration cap: `count($translationLanguages) * 2 + 2`. Round 0 writes defaults; each translation language needs one round to stage `localize` and one to fill the new translation row.
6. Flush the logger (savingEntity + recorded errors → one log row + N entries).
7. Return the run's max severity (`info` for clean, `error` if anything raised).

## Why the loop has multiple passes

DataHandler's cmdMap collapses to `[$table][$uid][$command] = $value` — a second `localize` for the same parent uid silently overwrites the first. Each translation language therefore needs its own round: round N stages exactly one localize (which materializes the translation row), round N+1 picks up the new translation uid via `promoteNewKeys` and writes its translated fields via the translations bucket.

`ResolverContext::defaultStatus` and `translationStatus` keep re-resolution idempotent across rounds — already-drained payloads short-circuit instead of re-fetching or re-querying.

## Logging contract

- Every run produces one `tx_thuecat_import_log` row with N `tx_thuecat_import_log_entry` children.
- Entry types: `savingEntity` (one per default-language row inserted/updated), `dataHandlerError` (one per `errorLog[]` line DataHandler raised), `mappingError` / `fetchingError` (one per caught exception in the URL loop).
- Severity vocabulary is PSR-3 (`debug` … `emergency`). DataHandler errors and caught exceptions are recorded as `error`; savingEntity rows are `info`.
- Editors filter the BE list view by `severity`. The `Command::SUCCESS`/`FAILURE` exit code is driven from `ImportLogger::getMaxSeverity()` — anything `>= error` fails the command.
- DataHandler's `enableLogging` stays `true`. Setting it false would short-circuit `errorLog` too, so we accept the duplicate write to `sys_log` and let editors filter there.

## Extending the importer

- **New imported type**: add an `Entity` class in `Parser/Entity/`, declare its TCA + DB columns, register the service-locator tag `import.entity`. The parser picks it up via `handlesTypes()`. If translatable, mirror an existing translatable TCA (e.g. `tx_thuecat_organisation`).
- **New URL source**: implement `UrlProvider`, register tag `import.url.provider`, and add a matching `type` string to `ImportConfiguration::getType()` plus its FlexForm.
- **New transient field**: extend the relevant `Entity::parse()` to push refs into the payload's `transients` bucket; teach `Resolver` to drain the new key.
- **New log severity / type**: extend `ImportLogger::SEVERITY_*`, the TCA select-list on `tx_thuecat_import_log_entry.type`, and the `xlf` labels.

## Testing

- Functional tests live in `Tests/Functional/`, base class `AbstractImportTestCase`. HTTP is staged via `GuzzleClientFaker` (file-keyed by URL); use `expectFetch()` / `expectNotFound()` / `expectFetchForUrl()` per scenario.
- Fixtures: payload data sets in `Tests/Functional/Fixtures/Import/*.php`, JSON-LD response bodies under `Tests/Functional/Fixtures/Import/Guzzle/<domain>/<path>/<id>.json`.
- Assertions: `Tests/Functional/Assertions/Import/*.php` define expected DB state after import.
- Run via `ddev phpunit thuecat functional`. Single file: append a test path. Static analysis: `ddev phpstan thuecat`. Style: `ddev php-cs-fixer thuecat`.