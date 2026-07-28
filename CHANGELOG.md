# Changelog

All notable changes to `danielme85/laravel-log-to-db` are documented here. Format loosely follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [5.0.0] - 2026-07-28

> **⚠️ Upgrading from v4?** See the datetime corruption notice in the readme and run `php artisan log:fix-datetime`.

- Support Laravel 12 and 13, PHP 8.2+
- Fix the default `datetime_format` (`Y-m-d H:i:s:ms`, an invalid PHP `date()` token) that corrupted the stored
  `datetime` column in v4; new default is `Y-m-d H:i:s`
- Add `php artisan log:fix-datetime` command to recompute `datetime` from `unix_time` on existing v4 rows
- Remove `AuthenticatedUserProcessor`
- Fix `unix_time` drift on queued log events: it now reflects the original log event time instead of when a
  delayed queue worker happened to process the job
- Fix a per-channel `model` override being ignored by `log:delete` / `log:fix-datetime`, which could silently
  operate on the wrong table
- Fix a per-channel `datetime_format` override not being applied when writing records
- `log:fix-datetime` now skips records with a missing/non-numeric `unix_time` instead of corrupting their
  `datetime` to the 1970-01-01 epoch
- Fix various stability bugs, general hardening and code cleanup
- Add top-of-readme warning linking to the v4 → v5 datetime upgrade notes

## [4.1.2] - 2025-03-04

- Fix `SaveNewLogEvent` job dispatch and `LogToDB` instance resolution inside the queued job
- Refactor delete loops to use queries instead of iterating models
- Update testing to PHP 8.4 on GitHub Actions, drop Laravel 10 from the test matrix in favor of Laravel 12
- Explicit nullable types for PHP 8.4 compatibility
- MongoDB Eloquent behavior tweaks/testing fixes

## [4.1.1] - 2024-02-24

- Prepare for Laravel 11 support (#61)

## [4.1.0] - 2023-09-18

- Compatibility updates and bugfixes (#60)

## [4.0.0] - 2023-02-22

- Add Laravel 10 support

## [3.0.4] - 2023-02-22

- Limit `composer.json` to Laravel <10

## [3.0.3] - 2022-12-15

- Add `AuthenticatedUserProcessor`
- Add Facade
- Switch CI to GitHub Actions with Codecov coverage reporting
- Fix issues affecting Lumen

## [3.0.2] - 2021-10-26

- Fall back to PHP's native `error_log()` when a log event can't be saved to the database (emergency fallback)
- Move local testing to Docker containers

## [3.0.1] - 2021-01-26

- Fix migration file being published/included twice
- Switch CI from Travis to CircleCI

## [3.0.0] - 2021-01-26

- Change migration to a publishable asset instead of auto-loading it from the service provider
- Use the configured connection when running migrations
- Use the collection name as the table name
- Add code coverage reporting

## [2.4.2] - 2020-10-13

- Fix `LogCleanerUpper` command
- Handle non-object exceptions in context
- Make MongoDB an optional suggested dependency

## [2.4.1] - 2020-09-02

- Fix `LogCleanerUpper` static model resolution when channel config specifies connection/collection (#28)

## [2.4.0] - 2020-07-10

- Add Laravel 7 support
- Add Lumen install instructions
- Default config array fallback when config file is missing
- Avoid closures in exception traces to prevent serialization issues

## [2.3.3] - 2020-06-04

- Fix context array only being JSON-encoded when it contained an exception; JSON encoding now happens consistently
  at the model layer

## [2.3.2] - 2020-05-06

- Improve exception handling to avoid exception logging loops

## [2.3.1] - 2020-05-04

- Parse exceptions before queueing so they can be logged correctly by queue workers
- Detect exceptions by class name, not just by `instanceof`

## [2.3.0] - 2020-04-29

- Add support for custom Eloquent models
- Add `LogException`, which is ignored by the log itself to avoid spam loops

## [2.2.0] - 2020-03-16

- Serialize datetime using the format from config

## [2.1.1] - 2020-02-04

- Add `removeOlderThan` / `removeOldestIfMoreThan` cleanup helpers and `LogCleanerUpper` command
- Add max log age config option

## [2.1.0] - 2020-01-09

- Fix `removeOldestIfMoreThan` / `removeOlderThan` naming and MongoDB behavior

## [2.0.2] - 2019-10-11

- Relax datetime object type checks for Laravel 5/6 compatibility

## [2.0.1] - 2019-10-04

- Make log processors configurable and opt-in; `IntrospectionProcessor` no longer included by default

## [2.0.0] - 2019-09-12

- Add Laravel 6 support

## [1.1.3] - 2019-09-12

- Dispatch `SaveNewLogEvent` synchronously for exceptions instead of queueing
- Fix missing processors when using the `stack` log channel

## [1.1.2] - 2019-03-07

- Config logic bugfix, readme fixes

## [1.1.1] - 2019-03-07

- Add `.env` support
- Rework the queued DB save approach; remove `max_rows` setting (unreliable with queued jobs) in favor of cleanup
  helper functions
- Skip logging when Laravel's kernel/config isn't loaded yet (e.g. during package discovery)

## [1.1.0] - 2018-08-20

- Make log save events queueable
- Add `created_at`/`updated_at` timestamps to the log table

## [1.0.0] - 2018-08-17

- First public release
