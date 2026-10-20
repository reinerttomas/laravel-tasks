<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
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
        $this->bootDevCommand();
    }

    private function bootDevCommand(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        // except() replaces the list, so repeat the queue exclusion that Horizon registers.
        DevCommands::except('server', 'queue');
    }
}
