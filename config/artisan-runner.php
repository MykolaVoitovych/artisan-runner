<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URL prefix for all Artisan Runner routes.
    */
    'route_prefix' => env('ARTISAN_RUNNER_PREFIX', 'artisan-runner'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    | The guard used to authenticate access to Artisan Runner. Set to null to
    | disable authentication entirely. Example: 'nova', 'web', 'sanctum'
    */
    'guard' => null,

    /*
    |--------------------------------------------------------------------------
    | Forbidden Commands
    |--------------------------------------------------------------------------
    | Commands that cannot be run via the web interface. Supports exact matches
    | and wildcard patterns (e.g. "migrate:*" blocks all migrate sub-commands).
    */
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
        'websockets:serve',
        'reverb:start',
        'octane:start',
        'octane:reload',
        'octane:stop',
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Timeout
    |--------------------------------------------------------------------------
    | Maximum number of seconds a command may run before being terminated.
    */
    'process_timeout' => env('ARTISAN_RUNNER_TIMEOUT', 300),
];
