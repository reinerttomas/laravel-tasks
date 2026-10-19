<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\DevCommands;

/**
 * @return list<string>
 */
function devCommandNames(): array
{
    return array_column(DevCommands::commands(), 'name');
}

test('dev command keeps the php server outside the local environment', function (): void {
    DevCommands::registerDefaults();

    (new AppServiceProvider(app()))->boot();

    expect(devCommandNames())->toContain('server');
});

test('dev command skips the php server locally because herd serves the app', function (): void {
    DevCommands::registerDefaults();
    app()->detectEnvironment(fn (): string => 'local');

    (new AppServiceProvider(app()))->boot();

    expect(devCommandNames())
        ->not->toContain('server')
        ->toContain('queue');
});
