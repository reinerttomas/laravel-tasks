<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUser
{
    /**
     * Update the user's profile, requiring verification again when the email changes.
     */
    public function handle(User $user, string $name, string $email): void
    {
        $user->fill([
            'name' => $name,
            'email' => $email,
        ]);

        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();
    }
}
