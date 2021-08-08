<?php

namespace NathanHeffley\LaravelWatermelon;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WatermelonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/watermelon.php', 'watermelon');

        $this->app->singleton(SyncService::class, function () {
            return new SyncService(config('watermelon.models'));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/watermelon.php' => base_path('config/watermelon.php'),
        ], 'watermelon-config');

        Route::middleware(config('watermelon.middleware'))->group(function () {
            Route::get(config('watermelon.route'), [SyncController::class, 'pull']);
            Route::post(config('watermelon.route'), [SyncController::class, 'push']);
        });
    }
}
