<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateTeam
{
    public function __construct(private SwitchTeam $switchTeam) {}

    /**
     * Create a new team and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal): Team {
            $team = Team::query()->create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $this->switchTeam->handle($user, $team);

            return $team;
        });
    }
}
