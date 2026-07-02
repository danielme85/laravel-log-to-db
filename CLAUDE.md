# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package that provides a Monolog-based logging channel to write Laravel log events to a database (SQL or MongoDB). Published as `danielme85/laravel-log-to-db`.

## Commands

### Install dependencies
```bash
composer install
```

### Run tests in Docker (preferred, no local DB needed)
```bash
./runLocalTestInDocker.sh
```
This starts MariaDB + MongoDB containers, runs phpunit in a PHP 8.4 container, then tears everything down. **Always try this first when running tests.**

### Run tests locally (requires MySQL and MongoDB running locally)
```bash
vendor/bin/phpunit
```

### Run a single test
```bash
vendor/bin/phpunit --filter testMethodName
```

### Run tests with coverage
```bash
vendor/bin/phpunit --coverage-clover ./coverage.xml
```

### Test database requirements (local only)
Tests expect MySQL at `127.0.0.1:3306` (root/root, database `logtodb`) and MongoDB at `127.0.0.1:27017` (database `logtodb`). See `.env.testing`.

## Architecture

### Log event data flow
```
Laravel Log facade
  → LogToDbHandler::__invoke (Monolog channel factory, used as 'via' in logging config)
    → LogToDbCustomLoggingHandler::write (extends Monolog AbstractProcessingHandler)
      → LogToDB::newFromMonolog(LogRecord)
        → sync: LogToDB::safeWrite() → Model->generate()->save()
        → async: dispatch(SaveNewLogEvent) queue job → safeWrite()
```

### Key source files
- `src/LogToDbHandler.php` — Invokable channel factory that creates the Monolog Logger with handler and processors
- `src/LogToDbCustomLoggingHandler.php` — Monolog handler that delegates to `LogToDB`
- `src/LogToDB.php` — Core class: model selection, record writing, emergency fallback, cleanup logic
- `src/Models/DBLog.php` — SQL Eloquent model
- `src/Models/DBLogMongoDB.php` — MongoDB Eloquent model
- `src/Models/BindsDynamically.php` — Trait for runtime table/connection binding on models
- `src/Models/LogToDbCreateObject.php` — Trait with `generate()` method mapping LogRecord to model attributes, plus JSON accessors/mutators and cleanup helpers
- `src/Jobs/SaveNewLogEvent.php` — Queueable job for async log writes
- `src/Commands/LogCleanerUpper.php` — `php artisan log:delete` command
- `src/Commands/LogDatetimeFixer.php` — `php artisan log:fix-datetime` command; recomputes the `datetime` column from `unix_time`, for repairing rows saved with the broken pre-v5 `datetime_format`
- `src/config/logtodb.php` — Default package configuration

### Dual database support
`LogToDB::getModel()` inspects the database connection driver at runtime to choose between `DBLog` (SQL) and `DBLogMongoDB` (MongoDB). Both models use the same traits (`BindsDynamically` + `LogToDbCreateObject`).

### Emergency fallback
If saving to DB throws any exception, `LogToDB::emergencyLog()` falls back to PHP's native `error_log()` via Monolog's `ErrorLogHandler` — log events are never silently lost.

### Custom model support
Users can provide their own Eloquent model class via `LOG_DB_MODEL` env var. Custom models need to `use LogToDbCreateObject` trait.

### Config priority
Channel-level config in `logging.php` > `.env` vars > `config/logtodb.php` defaults.

### v4 → v5 datetime_format fix
v4's default `datetime_format` (`Y-m-d H:i:s:ms`) used an invalid PHP date() token (`:ms`), corrupting stored `datetime`
values. v5 changes the default to `Y-m-d H:i:s`. Existing rows aren't auto-corrected; users run
`php artisan log:fix-datetime` to recompute `datetime` from the always-reliable `unix_time` column.

## CI

GitHub Actions (`.github/workflows/unittest.yml`): matrix of Laravel 12/13 with MySQL and MongoDB services. Coverage uploaded to Codecov.
