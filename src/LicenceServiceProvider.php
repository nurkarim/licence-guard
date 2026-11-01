<?php

namespace Epixel\LicenceGuard;

use Epixel\LicenceGuard\Http\Middleware\CheckLicence;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LicenceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'licence-guard');

        Route::middleware('web')->group(function () {
            Route::get('/licence', [\Epixel\LicenceGuard\Http\Controllers\LicenceController::class, 'show'])->name('licence-guard.activate');
            Route::post('/licence', [\Epixel\LicenceGuard\Http\Controllers\LicenceController::class, 'store'])->name('licence-guard.store');
        });

        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->make(Kernel::class)->pushMiddleware(CheckLicence::class);
    }
}
