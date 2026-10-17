<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureTeamMembership;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // A route whose action does not bind the team, so the middleware sees the bare slug.
    Route::middleware(['web', 'auth', EnsureTeamMembership::class])
        ->get('{current_team}/membership-probe', fn (): string => 'ok');
});

test('a team slug that is not bound to a model is resolved for members', function () {
    $membership = Membership::factory()->create();

    $this->actingAs($membership->user)
        ->get('/'.$membership->team->slug.'/membership-probe')
        ->assertOk();
});

test('an unbound team slug of another team is forbidden', function () {
    $membership = Membership::factory()->create();

    $this->actingAs($membership->user)
        ->get('/'.Team::factory()->create()->slug.'/membership-probe')
        ->assertForbidden();
});
