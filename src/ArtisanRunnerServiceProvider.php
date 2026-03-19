<?php

namespace Vantage\ArtisanRunner;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Vantage\ArtisanRunner\Http\Controllers\ArtisanRunnerController;

class ArtisanRunnerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/artisan-runner.php',
            'artisan-runner'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'artisan-runner');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/artisan-runner.php' => config_path('artisan-runner.php'),
            ], 'artisan-runner-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'artisan-runner-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/artisan-runner'),
            ], 'artisan-runner-views');
        }

        if (config('artisan-runner.enabled', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $middleware = config('artisan-runner.middleware', ['web']);

        if ($guard = config('artisan-runner.guard')) {
            $middleware[] = 'auth:' . $guard;
        }

        Route::middleware($middleware)
            ->prefix(config('artisan-runner.route_prefix', 'artisan-runner'))
            ->name('artisan-runner.')
            ->group(function (): void {
                Route::get('/', [ArtisanRunnerController::class, 'index'])->name('index');
                Route::post('/', [ArtisanRunnerController::class, 'store'])->name('store');
                Route::get('/{commandLog}', [ArtisanRunnerController::class, 'show'])->name('show');
                Route::get('/{commandLog}/output', [ArtisanRunnerController::class, 'output'])->name('output');
                Route::delete('/{commandLog}', [ArtisanRunnerController::class, 'destroy'])->name('destroy');
            });
    }
}
