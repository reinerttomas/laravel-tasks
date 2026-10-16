<?php

declare(strict_types=1);

use App\Models\User;
use App\Rules\ValidTeamInvitation;
use Illuminate\Support\Facades\Validator;

test('rejects a value that is not a team invitation', function () {
    $user = User::factory()->create();

    $validator = Validator::make(
        ['invitation' => 'not-an-invitation'],
        ['invitation' => [new ValidTeamInvitation($user)]],
    );

    expect($validator->errors()->first('invitation'))->toBe('This invitation was sent to a different email address.');
});
