<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

arch()->preset()->php();
// Eloquent scopes and accessors must stay protected, which strict() forbids.
arch()->preset()->strict()->ignoring('App\Models');
arch()->preset()->laravel();
arch()->preset()->security();

arch('models keep the strict rules that apply to them')
    ->expect('App\Models')
    ->toBeFinal()
    ->toUseStrictTypes()
    ->toUseStrictEquality();

arch('controllers are only reached through routes')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('actions are final readonly classes')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('actions do not carry an action suffix')
    ->expect('App\Actions')
    ->not->toHaveSuffix('Action');

arch('actions receive request data instead of reading it')
    ->expect('App\Actions')
    ->not->toUse(['auth', 'request', 'session']);

arch('urls come from named routes')
    ->expect(['url', 'action'])
    ->not->toBeUsed();

arch('auth and config are accessed through helpers')
    ->expect([Auth::class, Config::class])
    ->not->toBeUsed();

arch('dates are immutable')
    ->expect([Carbon::class, IlluminateCarbon::class])
    ->not->toBeUsed();
