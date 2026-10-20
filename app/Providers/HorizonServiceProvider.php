<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

/**
 * Replaces Horizon's HorizonApplicationServiceProvider, whose protected gate() hook the strict arch preset forbids.
 */
final class HorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null): bool => in_array(
            $user?->email,
            config()->array('horizon.allowed_emails'),
            strict: true,
        ));

        Horizon::auth(fn (): bool => $this->app->environment('local')
            || Gate::allows('viewHorizon'));
    }
}
