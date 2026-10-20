<?php

declare(strict_types=1);

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->expired()
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::command('horizon:snapshot')->everyFiveMinutes();
