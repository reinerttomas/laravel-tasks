<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

test('new records get a UUIDv7 primary key', function (string $modelClass) {
    /** @var Model $model */
    $model = $modelClass::factory()->create();

    expect(Str::isUuid($model->getKey(), 7))->toBeTrue()
        ->and($model->fresh()?->getKey())->toBe($model->getKey());
})->with([
    'user' => User::class,
    'team' => Team::class,
    'membership' => Membership::class,
    'team invitation' => TeamInvitation::class,
]);

test('attaching a member through either side of the relationship creates a UUIDv7 membership', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $member->teams()->attach($team, ['role' => TeamRole::Member->value]);

    expect($team->memberships()->pluck('id')->all())
        ->toHaveCount(2)
        ->each(fn ($id) => $id->toBeString()->and(Str::isUuid($id->value, 7))->toBeTrue());
});
