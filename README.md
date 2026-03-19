# Artisan Runner

A Laravel package that lets you run Artisan commands through a web UI. Commands are executed via the queue, with real-time output streamed to an xterm.js terminal. Includes command history, forbidden command protection, and configurable authentication.

## Features

- Run any Artisan command from the browser
- Real-time output in an xterm.js terminal (ANSI colour support)
- Commands execute as queued jobs — the HTTP request returns immediately
- Full command history with status, exit code, duration, and who ran it
- Configurable forbidden commands list (exact match and wildcard patterns)
- Configurable authentication guard (`nova`, `web`, `sanctum`, or none)
- Re-run any previous command in one click

## Installation

Add the path repository and require the package in your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/artisan-runner"
        }
    ],
    "require": {
        "vantage/artisan-runner": "^1.0"
    }
}
```

Then install:

```bash
composer install
```

Run the migration to create the command logs table:

```bash
php artisan migrate
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=artisan-runner-config
```

This creates `config/artisan-runner.php`:

```php
return [
    // URL prefix for all routes
    'route_prefix' => env('ARTISAN_RUNNER_PREFIX', 'artisan-runner'),

    // Middleware applied to all routes. Use ['nova-web'] for Nova integration.
    'middleware' => ['web'],

    // Authentication guard. Set to null to disable auth.
    // Examples: 'nova', 'web', 'sanctum'
    'guard' => null,

    // Commands that cannot be run via the web UI.
    // Supports exact matches and fnmatch() wildcard patterns.
    'forbidden_commands' => [
        'migrate:fresh',
        'migrate:reset',
        'db:wipe',
        'key:generate',
        'down',
        'serve',
        'tinker',
        'queue:listen',
        'queue:work',
        'octane:start',
        'octane:reload',
        'octane:stop',
    ],

    // Maximum seconds a command process may run before being terminated.
    'process_timeout' => env('ARTISAN_RUNNER_TIMEOUT', 300),
];
```

## Authentication

Set `guard` to any Laravel authentication guard to protect the routes:

```php
// Protect with Laravel Nova's guard
'guard' => 'nova',

// Protect with the default web guard
'guard' => 'web',

// No authentication (useful for local development only)
'guard' => null,
```

When using `nova`, the routes use the same session and CSRF middleware as Nova itself (`nova-web` group), so no extra setup is required.

## Usage

Navigate to `/artisan-runner` (or your configured prefix) after logging in.

### Running a command

Type the command name (without `php artisan`) into the input field and click **Run Command**. Autocomplete is available for all registered commands.

Examples:
```
cache:clear
migrate --force
queue:restart
config:cache
```

### Terminal output

Each command opens a dedicated terminal page with an xterm.js terminal. Output is polled every 300 ms from the queue worker and written to the terminal in real time, including ANSI colours.

### Command history

The index page lists all previous runs with:
- Command string
- Who ran it
- Status (Pending / Running / Completed / Failed)
- Exit code
- Time elapsed

Click **View** to reopen the terminal for any past run, or **Re-run** to dispatch the same command again.

### Deleting logs

Use the **Delete** button on the history list to remove a log entry.

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `ARTISAN_RUNNER_ENABLED` | `true` | Enable or disable all routes |
| `ARTISAN_RUNNER_PREFIX` | `artisan-runner` | URL prefix for all routes |
| `ARTISAN_RUNNER_TIMEOUT` | `300` | Max seconds a command may run |

## Limitations

### Commands that affect application runtime state

Because commands are dispatched as queued jobs and executed in a **separate worker process**, commands that clear or rebuild in-memory state will not affect the running application. For example:

- `cache:clear` — clears the cache store, but any values already loaded into memory by running Octane workers remain until they restart.
- `config:cache` / `config:clear` — writes or removes the cached config file, but running workers already have the old config loaded in memory.
- `route:cache` / `route:clear` — same issue; the compiled route file changes on disk but in-memory routing is unaffected.
- `view:cache` / `view:clear` — compiled views on disk change, but workers may still serve previously compiled views from memory.

If you use **Laravel Octane**, you will need to reload the workers after running any of these commands (`octane:reload`) for changes to take effect. That command is forbidden by default since it would also terminate this request.

If you do **not** use Octane and run PHP-FPM, these commands work as expected since each request spawns a fresh process.

## Queue

Commands run as `RunArtisanCommandJob` on the default queue connection. Make sure a queue worker is running:

```bash
php artisan queue:work
```

Each job has a single attempt (`tries = 1`) and respects the `process_timeout` config value. If a job fails (e.g. the worker is stopped mid-run), the terminal will display the error and mark the command as **Failed**.

## Forbidden Commands

Any command matching an entry in `forbidden_commands` will be rejected before the job is dispatched. Entries are checked using both exact string match and `fnmatch()`, so wildcards work:

```php
'forbidden_commands' => [
    'migrate:fresh',   // exact match
    'db:*',           // blocks db:wipe, db:seed, etc.
],
```

The forbidden list is displayed below the command input on the index page.

## Publishing Views

To customise the UI, publish the Blade views:

```bash
php artisan vendor:publish --tag=artisan-runner-views
```

Views will be copied to `resources/views/vendor/artisan-runner/`.
