<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Applies to any future password create/reset validation (no self-service
        // password change exists yet, but this is the project-wide default the
        // moment one is added, rather than something each new form has to remember).
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());
    }
}
