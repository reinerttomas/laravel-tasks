<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;

test('horizon metrics are captured every five minutes', function (): void {
    $snapshotEvents = collect(Schedule::events())
        ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'horizon:snapshot'));

    expect($snapshotEvents)->toHaveCount(1)
        ->and($snapshotEvents->first()->expression)->toBe('*/5 * * * *');
});
