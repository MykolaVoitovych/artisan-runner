# Artisan Runner

A Laravel package that lets you run Artisan commands through a web UI. Commands are executed via the queue, with real-time output streamed to an xterm.js terminal. Includes command history, forbidden command protection, and configurable authentication.

## Features

- Run any Artisan command from the browser
- Real-time output in an xterm.js terminal (ANSI colour support)
- Commands execute as queued jobs — the HTTP request returns immediately
- Full command history with status, exit code, duration, and who ran it
- Configurable middleware, forbidden commands list (exact match and wildcard patterns)
- Configurable authentication guard (`nova`, `web`, `sanctum`, or none)
- Re-run any previous command in one click

## Requirements

- PHP 8.4+
- Laravel 12+
- A running queue worker

## Installation

```bash
composer require vantage/artisan-runner
```

Run the migration:

```bash
php artisan migrate
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=artisan-runner-config
```

This creates `config/artisan-runner.php` with the following options:

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Enable or disable all routes |
| `route_prefix` | `'artisan-runner'` | URL prefix for all routes |
| `middleware` | `['web']` | Middleware applied to all routes |
| `guard` | `null` | Auth guard (`'nova'`, `'web'`, `'sanctum'`, or `null`) |
| `forbidden_commands` | see below | Commands blocked from running via the UI |
| `process_timeout` | `300` | Max seconds a command may run |

### Environment variables

| Variable | Default | Description |
|---|---|---|
| `ARTISAN_RUNNER_ENABLED` | `true` | Enable or disable all routes |
| `ARTISAN_RUNNER_PREFIX` | `artisan-runner` | URL prefix for all routes |
| `ARTISAN_RUNNER_TIMEOUT` | `300` | Max seconds a command may run |

## Authentication

Set `guard` to any Laravel authentication guard:

```php
'guard' => 'nova',  // Laravel Nova
'guard' => 'web',   // default web guard
'guard' => null,    // no authentication
```

### Nova integration

Set both `middleware` and `guard` for full Nova integration:

```php
'middleware' => ['nova-web'],
'guard'      => 'nova',
```

## Usage

Navigate to `/artisan-runner` (or your configured prefix).

Type a command name (without `php artisan`) and click **Run Command**. Autocomplete is available for all registered commands.

```
cache:clear
migrate --force
queue:restart
config:cache
```

Each command opens a dedicated terminal page. Output is polled every 300 ms and rendered with full ANSI colour support.

The history list shows all previous runs with status, exit code, duration, and who ran it. Use **Re-run** to dispatch the same command again, or **Delete** to remove the log entry.

## Forbidden Commands

Commands matching an entry in `forbidden_commands` are rejected before dispatch. Entries support exact matches and `fnmatch()` wildcard patterns:

```php
'forbidden_commands' => [
    'migrate:fresh',   // exact match
    'db:*',           // blocks db:wipe, db:seed, etc.
],
```

The forbidden list is displayed below the command input.

## Queue

Commands run as queued jobs. Make sure a worker is running:

```bash
php artisan queue:work
```

Each job has a single attempt and respects `process_timeout`. If the worker is stopped mid-run, the terminal displays the error and marks the command as **Failed**.

## Limitations

Commands run in a separate worker process, so commands that modify in-memory state (e.g. `config:cache`, `route:cache`, `view:clear`) will update files on disk but won't affect already-running workers. If you use **Laravel Octane**, run `octane:reload` after such commands for changes to take effect.

## Publishing Views

```bash
php artisan vendor:publish --tag=artisan-runner-views
```

Views will be copied to `resources/views/vendor/artisan-runner/`.
